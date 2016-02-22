<?php

include_once('scrap.php');
include_once('storage.php');

class RSSMetadata {
	public $rss_name, $title, $link, $description, $encoding = 'utf-8';
	public $get_links, $get_content;
	public $days_before_cleanup = 30;
	public $days_in_rss = 30;
	public $guid_postfix = 'DBG';
	public $getters = array();
	
	function __construct($rss_name, $title, $link, $description = '') {
		$this->rss_name = $rss_name;
		$this->title = $title;
		$this->link = $link;
		$this->description = $description;
	}
	
	function add_getter($get_arts, $get_content) {
		$get_content_ = function(&$art) use($get_content) {
			$art->saved = true;
			$art->tms = time();
			$get_content($art);
			if(!$art->content) $art->saved = false;
		};
		$this->getters[] = function(&$arts) use($get_arts, $get_content_) {
			foreach($get_arts() as $art) {
				$art->get_content = $get_content_;
				if($art->link) $arts[$art->link] = $art;
			}
		};
	}
	
	function get_arts() {
		$arts = array();
		foreach($this->getters as $getter)
			$getter($arts);
		return $arts;
	}
	
	function set_get_content($get_content) {
		$this->get_content = function(&$art) use($get_content) {
			$art->saved = true;
			$art->tms = time();
			$get_content($art);
			if(!$art->content) $art->saved = false;
		};
		$this->add_getter($this->get_links, $this->get_content);
	}
}

class RSSEngine {
	public $data;
	public $storage;
	public $rss;
	
	function __construct($data) {
		$this->data = $data;
	}
	
	function fetch() {
		$data =& $this->data;
		echo "Fetching $data->rss_name...<br><br>\n\n";
		$pre_t = microtime(true);
		$arts = $data->get_arts(); if(!$arts) return;
		$this->storage = new Storage($data->rss_name);
		$this->storage->fetch_articles($arts);
		foreach($arts as $link => &$art)
			if(!$art->saved) {
				$art->get_content();
				if($art->saved) $this->storage->add_article($art);
			}
		uasort($arts, function($a, $b) { return $b->tms - $a->tms; });
		$this->rss_begin();
		foreach($arts as $link => &$art)
			if($art->tms >= time() - $data->days_in_rss*24*60*60)
				$this->rss_add_item($art);
		$this->rss_close();
		$this->file_save("rss/$data->rss_name.rss", $this->rss);
		$this->storage->clean_old($data->days_before_cleanup);
		echo "<br>Elapsed " . (microtime(true) - $pre_t) . "<br><br>\n\n";
	}
	
	function rss_begin() {
		$data =& $this->data;
		$ret = '<?xml version="1.0" encoding="'.$data->encoding.'" ?>'."\n";
		$ret .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
		$date = date(DATE_RSS);
		$ret .= "<channel>\n<title>$data->title</title>\n";
		$ret .= "<lastBuildDate>$date</lastBuildDate>\n";
		$ret .= "<description>$data->description</description>\n";
		$ret .= "<link>$data->link</link>\n\n";
		$this->rss = $ret;
	}
	
	function rss_close() {
		$this->rss .= "</channel>\n</rss>\n";
	}
	
	function rss_add_item($art) {
		$ret = "<item>\n";
		$ret .= "<title><![CDATA[$art->title]]></title>\n";
		$ret .= "<pubDate>".date(DATE_RSS, $art->tms)."</pubDate>\n";
		$ret .= "<link><![CDATA[$art->link]]></link>\n";
		$guid = $art->saved ? $art->link : "NOT YET $art->link";
		$guid .= $this->data->guid_postfix;
		$ret .= '<guid isPermaLink="false">' . "<![CDATA[$guid]]></guid>\n";
		if($art->content)
			$ret .= "<description><![CDATA[$art->content]]></description>\n";
		if($art->author)
			$ret .= "<dc:creator><![CDATA[$art->author]]></dc:creator>\n";
		$ret .= "</item>\n\n";
		$this->rss .= $ret;
	}
	
	function file_save($filename, $content) {
		$f = fopen($filename, "w");
		fwrite($f, $content);
		fclose($f);
	}
}

function htmlenc($s, $enc = null) {
	if(!$enc) $enc = mb_detect_encoding($s);
	return mb_convert_encoding(trim($s), 'HTML-ENTITIES', $enc);
}
function google_cache($url) {
	return 'http://webcache.googleusercontent.com/search?q=cache:' . urlencode(str_before($url, '?'));
}
function post_context($post) {
	return stream_context_create(array(
		'http' => array(
			'method'  => 'POST',
			'content' => http_build_query($post)
		)
	));
}

class ScrapperNode {
	public $node, $html;
	function __construct($node, &$html) { $this->node = $node; $this->html =& $html; }
	function text() { return $this->node ? htmlenc($this->node->textContent) : ''; }
	function html() { return $this->node ? htmlenc($this->html->saveHTML($this->node)) : ''; }
	function attr($attr) { return $this->node ? htmlenc($this->node->getAttribute($attr)) : ''; }
}

class Scrapper {
	public $html, $xpath, $enc;
	
	//$params is a subset of array('xml', 'google_cache', 'silence', 'encoding', 'clean_aside', 'post')
	function __construct($url, $params = array()) {
		$xml = in_array('xml', $params);
		$gcache = in_array('google_cache', $params);
		if($gcache) $url = google_cache($url);
		$pre_t = microtime(true);
		$ctx = isset($params['post']) ? post_context($params['post']) : NULL;
		$source = @file_get_contents($url, false, $ctx);
		$this->html = new DOMDocument();
		$this->enc = isset($params['encoding']) ? $params['encoding'] : 'utf-8';
		if(!$xml) @$this->html->loadhtml(htmlenc($source, $this->enc));
		else @$this->html->loadxml($source);
		$this->xpath = new DOMXpath($this->html);
		$this->clean(in_array('clean_aside', $params));
		if(!in_array('silence', $params))
			echo $url . " " . (microtime(true) - $pre_t) . "<br>\n";
	}
	
	function clean($aside = false) {
		$nodes = array();
		foreach($this->html->getElementsByTagname('script') as $node)
			$nodes[] = $node;
		if($aside) foreach($this->html->getElementsByTagname('aside') as $node)
			$nodes[] = $node;
		foreach($nodes as $node)
			$node->parentNode->removeChild($node);
	}
		
	function _query($query, $ctx = null) {
		if(!$this->xpath) return array();
		return !$ctx ? $this->xpath->query($query) :
				$this->xpath->query($query, $ctx);
	}
	
	function node($query, $ctx = null) {
		$node = $this->_query($query, $ctx);
		$node = $node ? $node->item(0) : null;
		return new ScrapperNode($node, $this->html, $this->enc);
	}
	
	function query($query, $ctx = null) {
		$nodes = array();
		foreach($this->_query($query, $ctx) as $node)
			$nodes[] = new ScrapperNode($node, $this->html, $this->enc);
		return $nodes;
	}
	
	function extract_articles() {
		$arts = array();
		foreach($this->_query('//item') as $item) {
			$link = $this->node('link', $item)->text();
			$title = $this->node('title', $item)->text();
			$author = $this->node('author', $item)->text();
			if(!$author) @$author = $this->node('dc:creator', $item)->text();
			$description = $this->node('description', $item)->text();
			@$content = $this->node('content:encoded', $item)->text();
			$arts[$link] = new Article($link, $title, $author, $description.$content);
		}
		return $arts;
	}
}

?>