<?php

date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(60*60);

function stripAccents($str) {
    return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
class ScrapperNode {
	public $node, $html;
	function __construct($node, &$html) { $this->node = $node; $this->html =& $html; }
	function text() { return $this->node ? trim($this->node->textContent) : ''; }
	function html() { return $this->node ? trim($this->html->saveHTML($this->node)) : ''; }
	function attr($attr) { return $this->node ? trim($this->node->getAttribute($attr)) : ''; }
}
class Scrapper {
	public $html, $xpath;
	function __construct($source) {
		$this->html = new DOMDocument();
		@$this->html->loadhtml($source);
		$this->xpath = new DOMXpath($this->html);
	}		
	function _query($query, $ctx = null) {
		if(!$this->xpath) return array();
		return !$ctx ? $this->xpath->query($query) :
				$this->xpath->query($query, $ctx);
	}	
	function node($query, $ctx = null) {
		$node = $this->_query($query, $ctx);
		$node = $node ? $node->item(0) : null;
		return new ScrapperNode($node, $this->html);
	}	
	function query($query, $ctx = null) {
		$nodes = array();
		foreach($this->_query($query, $ctx) as $node)
			$nodes[] = new ScrapperNode($node, $this->html);
		return $nodes;
	}
}

class Tapas {
	
	public $t;
	
	function __construct() {
		$this->t = time();
	}

	function ejes() {
		$ret = array();
		$s = new Scrapper(file_get_contents('http://portal.ejes.com/tapas-del-dia/'));
		$tapas = array('Nacion', 'Herald', 'Pagina', 'Tiempo', 'Pais',
			           'Clarin', 'Cronista', 'Ambito', 'BAE', 'Perfil', 'Economista', 'Estadista');
		foreach($s->query('//ul[@class="tapitas"]//a') as $a)
			foreach($tapas as $tapa) {
				$diario = str_replace(" ", "", stripAccents($a->text()));
				if(strpos($diario, $tapa) !== false) {
					$t = new Scrapper(file_get_contents($a->attr('href')));
					foreach($t->query('//img') as $img)
						if(!isset($ret[$diario])) $ret[$diario] = $img->attr('src');
				}
			}
		return $ret;
	}

	function newseum($tapa) {
		$day = (string)(int)date("d", $this->t);
		$url = "http://webmedia.newseum.org/newseum-multimedia/dfp/pdf$day/$tapa.pdf";
		return $url;
	}

	function download_diarios($diarios) {
		$y = date("Y", $this->t);
		$m = date("m", $this->t);
		$d = date("d", $this->t);
		$dir = "tapas/$y/$m/$d";
		if (!file_exists($dir))
		    mkdir($dir, 0777, true);
		foreach ($diarios as $diario => $url) {
			$ext = endsWith($url, 'pdf') ? 'pdf' : 'jpg';
			@$f = fopen($url, 'r');
			if($f) file_put_contents("$dir/$diario.$ext", $f);
		}
	}

	function download() {
		$this->download_diarios($this->ejes());
		$diarios = array();
		$news = array('Ambito' => 'ARG_DAF', 'BuenosAiresHerald' => 'ARG_BAH', 'LaNacion' => 'ARG_LN', 'Clarin' => 'ARG_CLA');
		foreach ($news as $diario => $tapa)
			$diarios[$diario] = $this->newseum($tapa);
		$this->download_diarios($diarios);
	}
}

$tapas = new tapas();
$tapas->download();

?>
