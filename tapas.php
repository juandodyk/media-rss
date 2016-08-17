<html>
<body>

<?php

include_once('engine.php');
include_once('scrap.php');

date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(60*5);

class Tapas {
	
	public $t;
	
	function __construct() {
		$this->t = time();
	}
	
	function days_before($days) {
		$this->t -= $days*24*60*60;
	}

	function ejes() {
		$ret = '';
		$s = new Scrapper('http://portal.ejes.com/tapas-del-dia/', array('silence'));
		$tapas = array('Naci&oacute;n', 'Herald', 'P&aacute;gina', 'Tiempo', 'El Pa&iacute;s',
			           'Clar&iacute;n', 'Cronista', 'Ambito', 'BAE', 'Perfil', 'Economista', 'Estadista');
		foreach($s->query('//ul[@class="tapitas"]//a') as $a)
			foreach($tapas as $tapa) if(strpos($a->text(), $tapa) !== false) {
				$t = new Scrapper($a->attr('href'), array('silence'));
				foreach($t->query('//img') as $img)
					$ret .= '<img src="' . $img->attr('src') . '" style="width:100%;"><br>';
			}
		return $ret;
	}

	function ejes_() {
		$ret = '';
		$s = htmlenc(@file_get_contents('http://titulares.ejes.com/titulares.cfm'));
		$tapas = array('Naci&oacute;n', 'Clar&iacute;n', 'P&aacute;gina', 'Tiempo', 'Perfil', 'El Pa&iacute;s', 'Herald', 
			           '&Aacute;mbito', 'Ambito', 'Cronista', 'BAE', 'Economista', 'Estadista');
		foreach ($tapas as $tapa) {
			$regex = '/img: \'([^\']*?)\'[^}]*'.$tapa.'/m';
			preg_match($regex, $s, $matches);
			if($matches[1]) {
				$url = str_replace("_movil", "", $matches[1]);
				$url = str_replace("titulares.ejes.com/preview", "media.ejes.com/clipping", $url);
				$ret .= '<img src="' . $url . '" style="width:100%;"><br>';
			}
		}
		return $ret;
	}

	function newseum($tapa) {
		$day = (string)(int)date("d", $this->t);
		$url = "http://webmedia.newseum.org/newseum-multimedia/dfp/pdf$day/$tapa.pdf";
		return '<iframe src="http://docs.google.com/gview?url=' . $url . '&embedded=true" style="width:100%; height:100%;" frameborder="0"></iframe><br>'; 
	}

	function kiosko($tapa) {
		$y = date("Y", $this->t);
		$m = date("m", $this->t);
		$d = date("d", $this->t);
		$url = "http://img.kiosko.net/$y/$m/$d/$tapa.jpg";
		return '<img src="' . $url . '" style="width:100%;"><br>';
	}

	function dsd($tapa) {
		$y = date("Y", $this->t);
		$m = date("m", $this->t);
		$d = date("d", $this->t);
		$url = "http://www.eldsd.com/dsd/uploads/tapas_nuevo/$y-$m-$d"."_$tapa.jpg";
		return '<img src="' . $url . '" style="width:100%;"><br>';
	}

	function py_5dias() {
		$url = 'http://www.5dias.com.py/tapas/';
		$s = new Scrapper($url, array('silence'));
		foreach ($s->query('//div[@class="panel-content"]//div[@class="column3"]') as $div) {
			$fecha = $s->node('.//h3', $div->node)->text();
			if(!starts_with($fecha, date("d", $this->t))) continue;
			$img = $s->node('.//img', $div->node)->attr('src');
			return '<img src="' . $img . '" style="width:100%;"><br>';
		}
	}

	function py_lanacion() {
		$y = date("Y", $this->t);
		$m = date("m", $this->t);
		$d = date("d", $this->t);
		$url = 'http://www.lanacion.com.py/category/tapa/';
		$s = new Scrapper($url, array('silence'));
		foreach ($s->query('//article//a') as $a)
			if(strpos($a->attr('href'), "$y/$m/$d") !== false) {
				$s = new Scrapper($a->attr('href'), array('silence'));
				$img = $s->node('//div[@class="newsstand-blog-single-content"]/p//img')->attr('data-lazy-src');
				return '<img src="' . $img . '" style="width:100%;"><br>';
			}
	}

	function py_cva() {
		$ret = '';
		$ret .= '<img src="' . 'http://py.cvamedios.com/images/uh.jpg' . '" style="width:100%;"><br>';
		$ret .= '<img src="' . 'http://py.cvamedios.com/images/abc.jpg' . '" style="width:100%;"><br>';
		$ret .= '<img src="' . 'http://py.cvamedios.com/images/5dias.jpg' . '" style="width:100%;"><br>';
		$ret .= '<img src="' . 'http://py.cvamedios.com/images/lnpy.jpg' . '" style="width:100%;"><br>';
		return $ret;
	}

	function show() {
		$tapas = array(
			// $this->newseum('ARG_DAF'),
			// $this->dsd('05-EC'),
			// $this->kiosko('ar/ar_cronista'),
			// $this->dsd('13BAE'),
			// $this->dsd('02LANACION'),
			// $this->kiosko('ar/nacion'),
			// $this->newseum('ARG_CLA'),
			// $this->dsd('03PAG12'),
			// $this->kiosko('ar/ar_pagina12'),
			// $this->dsd('09TIEMPO'),
			// $this->kiosko('ar/tiempo_argentino'),
			// $this->newseum('ARG_BAH'),
			// $this->kiosko('ar/ar_perfil'),
			// $this->newseum('SPA_PAIS'),
			$this->ejes_(),
			$this->newseum('PAR_UH'),
			$this->py_5dias(),
			$this->py_lanacion(),
			$this->py_cva(),
			$this->newseum('WSJ'),
			$this->newseum('DC_WP'),
			$this->kiosko('uk/ft_uk'),
			$this->newseum('NY_NYT')
		);
		
		foreach($tapas as $tapa) {
			echo $tapa . "\n";
		}
	}
}

$tapas = new tapas();
if(isset($_GET['d'])) $tapas->days_before($_GET['d']);
$tapas->show();

?>

</body>
</html>
