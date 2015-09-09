<?php

include_once('engine.php');
include_once('scrap.php');

set_time_limit(60*5);

$datas = array();

$datas['ambito'] = function() {
	$url = "http://www.ambito.com";
	$data = new RSSMetadata("ambito", "Ambito Columnistas", $url);
	$data->encoding = $enc = "iso-8859-1";

	$data->get_links = function() use($url, $enc) {
		$arts = array();
		$s = new scrapper("$url/quinchos", array('encoding' => $enc));
		$link = $s->node('/html/head/meta[@property="og:url"]')->attr('content');
		$arts[$link] = new Article($link, "Charlas de quincho", "Quinchos");
		$s = new scrapper($url, array('encoding' => $enc));
		foreach($s->query('//div[@class="columnistasLista columnistas"]/div[@class="floatright"]') as $div) {
			$node = $s->node('./h2/a', $div->node);
			$link = $url . $node->attr('href');
			$title = $node->text();
			$author = $s->node('./h5', $div->node)->text();
			$arts[$link] = new Article($link, $title, $author);
		}		
		return $arts;
	};

	$data->set_get_content(function(&$art) use($enc) {
		$s = new scrapper($art->link, array('encoding' => $enc));
		$volanta = $s->node('//*[@class="volanta"]')->text();
		if($volanta) $volanta = "<i>$volanta</i><br><br>";
		$copete = $s->node('//*[@id="textoCopete"]')->html();
		if($copete) $copete = "<b>$copete</b><br><br>";
		$img = $s->node('//img[@id="imgDesp"]')->html();
		$epig = $s->node('//div[@id="epig"]')->html();
		$content = $s->node('//div[@id="textoDespliegue"]')->html();
		if(!$content) $art->saved = false;
		$art->content = "Por $art->author<br><br>$volanta$copete$img$epig$content";
	});
	
	return $data;
};

$datas['blanck'] = function() {
	$url = "http://www.clarin.com/autor/julio_blanck.html";
	$data = new RSSMetadata("blanck", "Julio Blanck", $url);
	
	$data->get_links = function() use($url) {
		$urls = array($url, "http://www.clarin.com/tema/julio_blanck.html");
		$arts = array();
		foreach($urls as $url) {
			$s = new scrapper($url);
			foreach($s->query('//li[@class="item"]') as $li) {
				$node = $s->node('.//a', $li->node);
				$link = "http://www.clarin.com" . $s->node('.//a', $li->node)->attr('href');
				$title = $node->attr('title');
				$arts[$link] = new Article($link, $title);
			}
		}
		return $arts;
	};
	
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = '';
		foreach($s->query('//div[@class="nota"]//p') as $p)
			$art->content .= $p->html();
	});
	
	return $data;
};

function rss_getter($url) {
	return function() use($url) {
		$s = new scrapper($url, array('xml'));
		return $s->extract_articles();
	};
}

function set_rss_getter(&$data, $url) {
	$data->get_links = rss_getter($url);
}

function set_economist_getters(&$data, $url) {
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$main = $s->node('//div[@class="main-content"]')->html();
		if($main) { $art->content = $main; return; }
		$s = new scrapper($url, array('google_cache'));
		$main = $s->node('//div[@class="main-content"]')->html();
		if($main) { $art->content = "USED GOOGLE CACHE<br><br>$main"; return; }
		$art->saved = false;
	});
}

$datas['econexplains'] = function() {
	$url = "http://www.economist.com/blogs/economist-explains/index.xml";
	$data = new RSSMetadata("econexplains", "The Economist explains", $url);
	set_economist_getters($data, $url);
	return $data;
};

$datas['econbuttonwood'] = function() {
	$url = "http://www.economist.com/blogs/buttonwood/index.xml";
	$data = new RSSMetadata("econbuttonwood", "Buttonwood's notebook", $url);
	set_economist_getters($data, $url);
	return $data;
};

$datas['econfreeexchange'] = function() {
	$url = "http://www.economist.com/blogs/freeexchange/index.xml";
	$data = new RSSMetadata("econfreeexchange", "Free exchange", $url);
	set_economist_getters($data, $url);
	return $data;
};

$datas['juvinformada'] = function() {
	$url = "http://www.juventudinformada.com.ar/category/noticias/feed/";
	$data = new RSSMetadata("juvinformada", "Juventud Informada", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@itemprop="articleBody"]')->html();
	});
	return $data;
};

$datas['lanacionwsj'] = function() {
	$url = "http://www.lanacion.com.ar/economia/the-wall-street-journal";
	$data = new RSSMetadata("lanacionwsj", "The Wall Street Journal", $url);
	$data->get_links = function() use($url) {
		$arts = array();
		$s = new scrapper($url);
		foreach($s->query('//section[@id="informacion1"]//article') as $art) {
			$a = $s->node('.//a[@class="info"]', $art->node);
			$link = "http://www.lanacion.com.ar" . $a->attr('href');
			$title = $s->node('h2', $a->node)->text();
			$as = array();
			foreach($s->query('.//span[@class="autor"]//b', $art->node) as $b)
				$as[] = $b->text();
			$author = implode(", ", $as);
			$arts[$link] = new Article($link, $title, $author);
		}
		return $arts;
	};
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//*[@id="cuerpo"]')->html();
	});
	return $data;
};

$datas['mincyt'] = function() {
	$url = "http://www.mincyt.gob.ar/xml/noticias.xml";
	$data = new RSSMetadata("mincyt", "Ministerio de Ciencia, Tecnologia e Innovacion Productiva", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content .= $s->node('//div[@class="cuerpo"]')->html();
	});
	return $data;
};

$datas['agenciatss'] = function() {
	$url = "http://www.unsam.edu.ar/tss/feed/";
	$data = new RSSMetadata("agenciatss", "Agencia TSS", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		foreach($s->query('//div[@class="post-wrapper"]//p') as $p)
			$art->content .= $p->html();
	});
	return $data;
};

$datas['paulkrugman'] = function() {
	$url = "http://krugman.blogs.nytimes.com/feed/";
	$data = new RSSMetadata("paulkrugman", "Paul Krugman", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link, array('google_cache'));
		$content = '';
		foreach($s->query('//div[@class="entry-content"]/*') as $p)
			if($p->attr('id') != "sharetools-story")
				$content .= $p->html();
		$art->content .= $content;
		if(!$content) $art->saved = false;
	});
	return $data;
};

$datas['resumenelpais'] = function() {
	$url = "http://elpais.com/agr/rss/el_resumen_del_dia/a";
	$data = new RSSMetadata("resumenelpais", "Resumen del dia - El Pais", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//*[@id="cuerpo_noticia"]')->html();
	});
	return $data;
};

$datas['cronistaft'] = function() {
	$url = "http://www.cronista.com/seccion/financial_times/";
	$data = new RSSMetadata("cronistaft", "Financial Times - El Cronista", $url);
	$data->get_links = function() use($url) {
		$arts = array();
		$s = new scrapper($url);
		foreach($s->query('//div[@class="overflow"]') as $div) {
			$node = $s->node('h2/a[@itemprop="name"]', $div->node);
			$link = "http://www.cronista.com" . $node->attr('href');
			$title = $node->text();
			$author = $s->node('div/a[@itemprop="name"]', $div->node)->text();
			$arts[$link] = new Article($link, $title, $author);
		}
		return $arts;
	};
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@class="bajada"]')->html();
		$art->content .= $s->node('//div[@itemprop="articleBody"]')->html();
	});
	return $data;
};

$datas['bloomrochekelly'] = function() {
	$url = "http://www.bloomberg.com/authors/ARIYqLnEHrg/lorcan-roche-kelly";
	$data = new RSSMetadata("bloomrochekelly", "Bloomberg - Roche Kelly", $url);
	$data->get_links = function() use($url) {
		$arts = array();
		$s = new scrapper($url);
		foreach($s->query('//a[@class="index-page__headline-link"]') as $a) {
			$link = 'http://www.bloomberg.com/' . $a->attr('href');
			$title = $a->text();
			$arts[$link] = new Article($link, $title);
		}
		return $arts;
	};
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@class="article-body__content"]')->html();
	});
	return $data;
};

$datas['bloomnoahsmith'] = function() {
	$url = "http://www.bloombergview.com/rss/contributors/noah-smith.rss";
	$data = new RSSMetadata("bloomnoahsmith", "Bloomberg View - Articles by Noah Smith", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@itemprop="articleBody"]')->html();
	});
	return $data;
};

$datas['bloombergview'] = function() {
	$data = new RSSMetadata("bloombergview", "Bloomberg View", "http://www.bloombergview.com/");
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content .= $s->node('//div[@class="quicktake_header"]/img')->html();
		$art->content .= $s->node('//img[@itemprop="image"]')->html();
		$art->content .= $s->node('//div[@class="quicktake_introduction"]')->html();
		$art->content .= $s->node('//div[@itemprop="articleBody"]')->html();
	};
	$get_arts = function($url, $author_='') { return function() use($url, $author_) {
		$arts = array();
		$s = new scrapper($url);
		foreach($s->query('//article/div[1]') as $div) {
			$a = $s->node('a', $div->node);
			$link = $a->attr('href'); $link = starts_with($link, '/') ? str_from($link, '/') : $link;
			$link = "http://www.bloombergview.com/" . $link;
			$title = $s->node('div[1]', $a->node)->text();
			if(!$title) $title = $a->text();
			$content = $s->node('div[2]', $a->node)->text();
			$author = $s->node('div[1]/span/a', $div->node)->text();
			$author = $author ? $author : $author_;
			$arts[] = new Article($link, $title, $author, $content);
		}
		return $arts;
	}; };
	$data->add_getter($get_arts("http://www.bloombergview.com/quicktake", "QuickTake"), $get_content);
	$data->add_getter($get_arts("http://www.bloombergview.com/topics/latin-america", "Latin America"), $get_content);
	$data->add_getter($get_arts("http://www.bloombergview.com/topics/economics", "Economics"), $get_content);
	$data->add_getter($get_arts("http://www.bloombergview.com/contributors/noah-smith", "Noah Smith"), $get_content);
	return $data;
};

$datas['aleberco'] = function() {
	$url = "http://www.diariobae.com/notas/category/columnistas/alejandro-bercovich";
	$data = new RSSMetadata("aleberco", "Alejandro Bercovich", $url);
	$get_arts = function() use($url) {
		$s = new scrapper($url);
		$arts = array();
		foreach($s->query('//div[@id="content"]//h2/a') as $a) {
			$link = $a->attr('href');
			$title = $a->text();
			$arts[] = new Article($link, $title);
		}
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@class="entry"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['cscaletta'] = function() {
	$url = "http://medium.com/feed/@ClaudioScaletta";
	$data = new RSSMetadata("cscaletta", "Claudio Scaletta", $url);
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@class="section-content"]')->html();
	};
	$data->add_getter(rss_getter($url), $get_content);
	return $data;
};

$func = array();
$cmd = array();

$cmd['s'] = "Fetch articles.";
$func['s'] = function($val) use($datas) {
	foreach(explode(',', $val) as $s) if(isset($datas[$s])) {
		$engine = new RSSEngine($datas[$s]());
		$engine->fetch();
	}
};

$cmd['dbg'] = "Debug: fetch articles without saving.";
$func['dbg'] = function($val) use($datas) {
	foreach(explode(',', $val) as $s) if(isset($datas[$s])) {
		$data = $datas[$s]();
		$arts = $data->get_arts();
		$get_content = array();
		foreach($arts as $link => &$art) {
			$get_content[$art->link] = $art->get_content;
			$art->get_content = null;
		}
		print_r($arts);
		foreach($arts as $link => &$art)
			$art->get_content = $get_content[$art->link];
		foreach($arts as $link => &$art) {
			$art->get_content();
			$art->get_content = null;
			print_r($art);
			exit;
		}
	}
};

$cmd['rm'] = "Remove table.";
$func['rm'] = function($val) use($datas) {
	foreach(explode(',', $val) as $s) if(isset($datas[$s])) {
		$storage = new storage($s);
		$storage->conn->query("delete from $s");
		echo "Erased $s<br><br>\n\n";
	}
};

$cmd['t'] = "Create table.";
$func['t'] = function($val) use($datas) {
	foreach(explode(',', $val) as $s) if(isset($datas[$s])) {
		$storage = new storage($s);
		$storage->create_table();
		echo "Created table $s<br><br>\n\n";
	}
};

$cmd['r'] = "Read articles.";
$func['r'] = function($val) use($datas) {
	foreach(explode(',', $val) as $name) if(isset($datas[$name])) {
		$storage = new Storage($name);
		if(!isset($_GET['link'])) {
			echo "<h1>" . $datas[$name]()->title . "</h1>";
			foreach($storage->last_articles() as $art)
				echo "<a href='retrieve.php?r=$name&link=" . urlencode($art->link) . "'>$art->title</a><br>";
			continue;
		}
		$link = urldecode($_GET['link']);
		$arts = array($link => new Article($link));
		$storage->fetch_articles($arts);
		$art = $arts[$link];
		echo "<html><head><title>$art->title</title></head>";
		echo "<body><h1>$art->title</h1><p>Por $art->author. <a href='$link'>Link</a> <a href='retrieve.php?r=$name'>Volver</a></p>";
		echo "$art->content</body></html>";
	}
};

echo implode(",", array_keys($datas)) . "<br><br>\n\n";

foreach($_GET as $key => &$val)
	if(!$val || $val == "all") $val = implode(",", array_keys($datas));
foreach($_GET as $key => &$val) if(isset($func[$key]))
	$func[$key]($val);
if(!$_GET) foreach($cmd as $c => $desc)
	echo "<b>$c</b> $desc<br>\n";

?>
