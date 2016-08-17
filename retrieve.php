<?php

include_once('engine.php');
include_once('scrap.php');

set_time_limit(60*5);
date_default_timezone_set('America/Argentina/Buenos_Aires');

function rss_getter($url, $max_arts=100) {
	return function() use($url, $max_arts) {
		$s = new scrapper($url, array('xml'));
		return $s->extract_articles($max_arts);
	};
}

function set_rss_getter(&$data, $url) {
	$data->get_links = rss_getter($url);
}

$ffc = new FiveFiltersContent();
$ff_get_content = function(&$art) use($ffc) {
	$art->content .= $ffc->get_content($art->link);
};

$datas = array();

/* Template

$datas['...'] = function() {
	$url = '...';
	$data = new RSSMetadata('...', '...', $url);
	$get_arts = function() use($url) {
		$arts = array();
		$s = new Scrapper($url);
		...
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new Scrapper($art->link);
		$art->content .= $s->node('...')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};
*/

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
		$s = new Scrapper("$url/noticias/buscar.asp?clave=Lo%20que%20se%20dice%20en%20las%20mesas");
		$link = $url . $s->node('//p[@class="nota"]/a')->attr('href');
		$arts[$link] = new Article($link, "Lo que se dice en las mesas");
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
		$content = preg_replace('#(?:<br\s*/?>\s*?)+#', '</p><p>', $content);
		$art->content = "Por $art->author<br><br>$volanta$copete$img$epig<p>$content</p>";
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
		$art->content = preg_replace('#(?:<br\s*/?>\s*?)+#', '</p><p>', $art->content);
	});
	
	return $data;
};

$economist_get_content = function(&$art) {
	$s = new scrapper($art->link, array('clean_aside'));
	$main = $s->node('//div[@class="main-content"]')->html();
	if($main) { $art->content = $main; return; }
	$s = new scrapper($url, array('google_cache'));
	$main = $s->node('//div[@class="main-content"]')->html();
	if($main) { $art->content = "USED GOOGLE CACHE<br><br>$main"; return; }
	$art->saved = false;
};

function set_economist_getters(&$data, $url, $getter) {
	set_rss_getter($data, $url);
	$data->set_get_content($getter);
}

$datas['econexplains'] = function() use($economist_get_content) {
	$url = "http://www.economist.com/blogs/economist-explains/index.xml";
	$data = new RSSMetadata("econexplains", "The Economist explains", $url);
	set_economist_getters($data, $url, $economist_get_content);
	return $data;
};

$datas['econbuttonwood'] = function() use($economist_get_content) {
	$url = "http://www.economist.com/blogs/buttonwood/index.xml";
	$data = new RSSMetadata("econbuttonwood", "Buttonwood's notebook", $url);
	set_economist_getters($data, $url, $economist_get_content);
	return $data;
};

$datas['econfreeexchange'] = function() use($economist_get_content) {
	$url = "http://www.economist.com/blogs/freeexchange/index.xml";
	$data = new RSSMetadata("econfreeexchange", "Free exchange", $url);
	set_economist_getters($data, $url, $economist_get_content);
	return $data;
};

$datas['econpoliticsweek'] = function() use($economist_get_content) {
	$url = "http://www.economist.com/printedition";
	$data = new RSSMetadata("econpoliticsweek", "The Economist - Politics this week", $url);
	$get_arts = function() use($url) {
		$s = new scrapper($url);
		$arts = array();
		foreach($s->query('//div[@class="article"]/a') as $a)
			if($a->text() == "Politics this week" || $a->text() == "The world this week" || $a->text() == "Politics") {
				$link = "http://www.economist.com". $a->attr('href');
				$title = $a->text();
				$arts[] = new Article($link, $title);
			}
		return $arts;
	};
	$data->add_getter($get_arts, $economist_get_content);
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
	$get_arts = function() use($url) {
		$s = new scrapper($url);
		$arts = array();
		foreach($s->query('//article/h2/a') as $a) {
			$link = "http://www.lanacion.com.ar". $a->attr('href');
			$title = $a->text();
			$arts[] = new Article($link, $title);
		}
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new scrapper($art->link, array('clean_aside'));
		$as = array();
		foreach($s->query('//div[@class="columnista"]//a') as $a)
			$as[] = $a->text();
		$art->author = implode(", ", $as);
		$art->content = $s->node('//section[@id="cuerpo"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['lanacion'] = function() {
	$data = new RSSMetadata("lanacion", "La Nacion Columnistas", "http://www.lanacion.com.ar/");
	$data->days_in_rss = 1;
	$get_arts_by = function($author) { return function() use($author) {
		$s = new Scrapper("http://www.lanacion.com.ar/autor/$author");
		$arts = array();
		$it = 0;
		foreach($s->query('//article/h2/a') as $a) {
			if($it++ > 3) break;
			$link = $a->attr('href');
			if(starts_with($link, "http")) continue;
			$link = "http://www.lanacion.com.ar" . $link;
			$title = $a->text();
			$arts[] = new Article($link, $title);
		}
		return $arts;
	};};
	$get_content = function(&$art) {
		$s = new scrapper($art->link, array('clean_aside'));
		$as = array();
		foreach($s->query('//a[@itemprop="author"]') as $a)
			$as[] = $a->text();
		$art->author = implode(", ", $as);
		$art->content = $s->node('//section[@id="cuerpo"]')->html();
	};
	$authors = array("eduardo-fidanza-614", "carlos-pagni-81", "francisco-olivera-179",
		             "gabriel-sued-165", "jaime-rosemberg-163", "lucrecia-bullrich-3",
		             "eduardo-levy-yeyati-319", "francisco-jueguen-12", "hugo-alconada-mon-97",
		             "nicolas-balinotti-152", "pablo-fernandez-blanco-3110", "silvia-pisani-120",
		             "florencia-donovan-175", "andres-malamud-5974", "juan-gabriel-tokatlian-756",
		             "federico-merke-4764", "nicolas-dujovne-706", "marcelo-leiras-1719",
		             "martin-kanenguiser-177", "jose-del-rio-6753", "alberto-armendariz-119",
		             "javier-blanco-170", "fernando-bertello-228", "martin-dinatale-11",
		             "sabrina-corujo-1791", "raquel-san-martin-151", "alejandro-rebossio-181");
	foreach($authors as $author)
		$data->add_getter($get_arts_by($author), $get_content);
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

$datas['paulkrugman'] = function() use($ff_get_content) {
	$url = "http://krugman.blogs.nytimes.com/feed/";
	$data = new RSSMetadata("paulkrugman", "Paul Krugman", $url);
	/*$get_content = function(&$art) {
		$get = function(&$s) {
			$content = '';
			foreach($s->query('//div[@class="entry-content" or @class="story-body"]/*') as $p)
				if(strpos($p->attr('id'), "sharetools") === false)
					$content .= $p->html();
			return $content;
		};
		$content = $get(new scrapper($art->link));
		if($content) { $art->content = $main; return; }
		$content = $get(new scrapper($art->link, array('google_cache')));
		if($content) { $art->content = "USED GOOGLE CACHE<br><br>$content"; return; }
		$art->saved = false;
	};
	$data->add_getter(rss_getter($url), $get_content);*/
	$get_links = function() {
		$arts = array(); $count = 1;
		$s = new scrapper("http://www.nytimes.com/column/paul-krugman");
		foreach($s->query('//a[@class="story-link"]') as $a) {
			$link = $a->attr('href');
			$title = $s->node('.//h2', $a->node)->text();
			$author = "Paul Krugman";
			$content = $s->node('.//p', $a->node)->html();
			$arts[$link] = new Article($link, $title, $author, $content);
			if($count++ >= 3) break;
		}
		return $arts;
	};
	$data->add_getter(rss_getter($url, 3), $ff_get_content);
	$data->add_getter($get_links, $ff_get_content);
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
		$art->content = preg_replace('#(?:<br\s*/?>\s*?)+#', '</p><p>', $art->content);
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

$datas['bloombergview'] = function() {
	$data = new RSSMetadata("bloombergview", "Bloomberg View", "http://www.bloombergview.com/");
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->title = $s->node('//meta[@property="og:title"]')->attr('content');
		$author = $s->node('//div[@class=" byline"]/a')->text();
		if($author) $art->author = $author;
		$art->content .= $s->node('//div[@class="quicktake_header"]/img')->html();
		$art->content .= $s->node('//img[@itemprop="image"]')->html();
		$art->content .= $s->node('//div[@class="quicktake_introduction"]')->html();
		$art->content .= $s->node('//div[@itemprop="articleBody"]')->html();
		$art->content .= $s->node('//div[@id="content"]//img')->html();
		$art->content .= $s->node('//article/div[2]')->html();
	};
	$data->add_getter(rss_getter("http://www.bloomberg.com/view/rss/topics/latin-america.rss", 3), $get_content);
	$data->add_getter(rss_getter("http://www.bloomberg.com/view/rss/topics/brazil.rss", 3), $get_content);
	$data->add_getter(rss_getter("http://www.bloomberg.com/view/rss/topics/economics.rss", 3), $get_content);
	$data->add_getter(rss_getter("http://www.bloomberg.com/view/rss/contributors/mohamed-a-el-erian.rss", 3), $get_content);
	$data->add_getter(rss_getter("http://www.bloomberg.com/view/rss/contributors/noah-smith.rss", 3), $get_content);
	return $data;
};

$datas['aleberco'] = function() {
	$url = "http://www.diariobae.com/tag/index/32933/alejandro-bercovich";
	$data = new RSSMetadata("aleberco", "Alejandro Bercovich", $url);
	$get_arts = function() use($url) {
		$s = new scrapper($url);
		$arts = array();
		foreach($s->query('//div[@id="content"]//h2/a') as $a) {
			$link = "http://www.diariobae.com/" . $a->attr('href');
			$title = $a->text();
			$arts[] = new Article($link, $title, "Alejandro Bercovich");
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

$datas['anfibia'] = function() {
	$url = 'http://www.revistaanfibia.com/';
	$data = new RSSMetadata('anfibia', "Revista Anfibia", $url);
	$get_arts = function() use($url) {
		$s = new Scrapper($url);
		$arts = array();
		foreach($s->query('//div[@class="cover"]') as $div) {
			$link = $s->node('div[@class="titulo"]/a', $div->node)->attr('href');
			$title = $s->node('.//h1', $div->node)->text();
			$authors = array();
			foreach($s->query('.//h4/a', $div->node) as $a)
				$authors[] = $a->text();
			$author = implode($authors, ', ');
			$content = $s->node('.//h3', $div->node)->html();
			$arts[] = new Article($link, $title, $author, $content);
		}
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content .= $s->node('//div[@id="content"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['monkeycage'] = function() {
	$url = 'http://www.washingtonpost.com/blogs/monkey-cage/';
	$data = new RSSMetadata('monkeycage', 'Monkey Cage', $url);
	$get_arts = function() use($url) {
		$s = new Scrapper($url);
		$arts = array();
		foreach($s->query('//div[contains(@class, "story-body")]') as $div) {
			$link = $s->node('.//h3/a', $div->node)->attr('href');
			$title = $s->node('.//h3/a', $div->node)->text();
			$author = $s->node('.//span[@class="author"]', $div->node)->text();
			$content = $s->node('.//div[@class="story-description"]', $div->node)->html();
			$arts[] = new Article($link, $title, $author, $content);
		}
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content .= $s->node('//div[@id="article-body"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['pagina12'] = function() {
	$url = 'http://www.pagina12.com.ar/diario/principal/index.html';
	$data = new RSSMetadata('pagina12', 'Pagina 12 Columnistas', $url);
	$data->encoding = $enc = "iso-8859-1";
	$get_arts = function() use($url, $enc) {
		$s = new Scrapper($url, array('encoding' => $enc));
		$arts = array();
		$authors = array('Pertot', 'Nepomuceno', 'Wainfeld', 'Verbitsky', 'Dellatorre',
			             'Scaletta', 'Lukin', 'Abrevaya', 'Natanson', 'Cecchi', 'Zizek', 'Torres Cabreros');
		$root = 'http://www.pagina12.com.ar';
		foreach($s->query('//div[@id="bloque_escriben_hoy"]/ul/li/a') as $a)
			foreach($authors as $name) if(strpos($a->text(), $name) !== false) {
				$link = $root . $a->attr('href');
				$author = $a->text();
				$links = array();
				if(strpos($link, '/autores/') !== false) {
					$s = new scrapper($link, array('encoding' => $enc));
					foreach ($s->query('//div[@class="noticia"]//a') as $a)
						$arts[] = new Article($root . $a->attr('href'), '', $author);
				} else
					$arts[] = new Article($root . $a->attr('href'), '', $author);
			}
		return $arts;
	};
	$get_content = function(&$art) use($enc) {
		$s = new scrapper($art->link, array('encoding' => $enc));
		$art->title = $s->node('//h2')->text();
		$art->content .= $s->node('//p[@class="volanta"]')->html();
		$art->content .= $s->node('//p[@class="intro"]')->html();
		$art->content .= $s->node('//div[contains(@class, "foto_nota")]')->html();
		$art->content .= $s->node('//div[@id="cuerpo"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['laizquierdadiario'] = function() {
	$url = 'http://www.laizquierdadiario.com/spip.php?page=backend';
	$data = new RSSMetadata('laizquierdadiario', 'La Izquierda Diario Columnistas', $url);
	$get_arts = function() use($url) {
		$arts = array();
		$s = new scrapper($url, array('xml'));
		$authors = array('Rosso', 'Bach');
		foreach($s->extract_articles() as $art)
			foreach($authors as $name) if(strpos($art->author, $name) !== false)
				$arts[] = $art;
		return $arts;
	};
	$get_content = function(&$art) {};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['nuevaciudad'] = function() {
	$url = 'http://nueva-ciudad.com.ar/';
	$data = new RSSMetadata('nuevaciudad', 'Nueva Ciudad Columnistas', $url);
	$data->encoding = $enc = "iso-8859-1";
	$get_arts = function() use($url, $enc) {
		$arts = array();
		$s = new Scrapper($url, array('encoding' => $enc));
		$authors = array('Casullo');
		foreach($s->query('//section[@class="columnistas"]//a') as $a)
			foreach($authors as $name)
				if(strpos($s->node('.//span[@class="author"]', $a->node)->text(), $name) !== false) {
					$link = 'http://nueva-ciudad.com.ar' . $a->attr('href');
					$title = $s->node('.//h2', $a->node)->text();
					$author = $s->node('.//span[@class="author"]', $a->node)->text();
					$content = $s->node('.//p', $a->node)->text();
					$arts[] = new Article($link, $title, $author, $content);
				}
		return $arts;
	};
	$get_content = function(&$art) use($enc) {
		$s = new Scrapper($art->link, array('encoding' => $enc));
		$art->content .= $s->node('//div[@class="description"]')->html();
		$art->content = preg_replace('#(?:<br\s*/?>\s*?)+#', '</p><p>', $art->content);
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['tiempo'] = function() {
	$url = 'http://tiempo.infonews.com/';
	$data = new RSSMetadata('tiempo', 'Tiempo Columnistas', $url);
	$get_arts = function() {
		$arts = array();
		$authors = array('432' => 'Claudio Mardones', '781' => 'Leandro Renou', '86' => 'Ana Vainman',
			             '696' => 'Antolin Magallanes', '828' => 'Ari Lijalad', '1755' => 'Diego Genoud');
		foreach($authors as $number => $author) {
			$s = new Scrapper("http://www.infonews.com/autor/$number");
			foreach($s->query('//div[@class="widget-open"]//div[@class="content-info"]//h2/a') as $a) {
				$link = $a->attr('href');
				$title = $a->text();
				$arts[] = new Article($link, $title, $author);
			}
		}
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new Scrapper($art->link);
		$art->content .= $s->node('//div[@itemprop="description"]')->html();
		$art->content .= $s->node('//div[@itemprop="articleBody"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['bastion'] = function() {
	$url = 'http://ar.bastiondigital.com/';
	$data = new RSSMetadata('bastion', 'Bastion Digital', $url);
	$get_arts = function($url) { return function() use($url) {
		$s = new Scrapper($url);
		$arts = array();
		foreach($s->query('//article') as $art) {
			$link = 'http://ar.bastiondigital.com' . $s->node('.//h2/a', $art->node)->attr('href');
			$title = $s->node('.//h2/a', $art->node)->text();
			$authors = array();
			foreach ($s->query('.//div[@class="teaser-autor"]/a', $art->node) as $div) if($div->text())
				$authors[] = $div->text();
			$author = implode(", ", $authors);
			$content = $s->node('.//div[contains(@class, "content")]', $art->node)->html();
			$arts[] = new Article($link, $title, $author, $content);
		}
		return $arts;
	};};
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content .= $s->node('//div[contains(@class, "field-name-body")]')->html();
	};
	$data->add_getter($get_arts($url."tipos-de-nota/dicen-en-bastion"), $get_content);
	$data->add_getter($get_arts($url."curaduria"), $get_content);
	return $data;
};

$datas['panama'] = function() {
	$url = "http://panamarevista.com/feed/";
	$data = new RSSMetadata("panama", "Panama Revista", $url);
	set_rss_getter($data, $url);
	$data->set_get_content(function(&$art) {
		$s = new scrapper($art->link);
		$art->author = $s->node('//span[@class="nombre"]')->text();
		$art->content = $s->node('//div[@class="autor"]')->html();
		foreach($s->query('//div[@class="post-entry"]/p') as $p)
			$art->content .= $p->html();
		$art->content = preg_replace('#(?:<br\s*/?>\s*?)+#', '</p><p>', $art->content);
	});
	return $data;
};

$datas['perfil'] = function() {
	$url = "http://www.perfil.com";
	$data = new RSSMetadata("perfil", "Perfil Columnistas", $url);
	$get_arts = function() use($url) {
		$arts = array();
		$authors = array('Fidanza', 'Delfino', 'Ayerdi', 'DerGhougassian', 'Luciano Cohan', 'Bilotta', 'Sarlo');
		foreach($authors as $nombre) {
			$t = time() - 7*24*60*60; $y = date("Y", $t); $m = date("m", $t); $d = date("d", $t); $t1 = "$d/$m/$y";
			$t = time(); $y = date("Y", $t); $m = date("m", $t); $d = date("d", $t); $t2 = "$d/$m/$y";
			$s = new Scrapper("$url/resultados.html", array('post' => array('search' => $nombre, 'desde' => $t1, 'hasta' => $t2)));
			$primero = true;
			foreach($s->query('//*[@id="seccion-arriba"]/article//a') as $a) {
				$link = $url . $a->attr('href');
				$title = $a->text();
				$t = new Scrapper($link);
				$author = $t->node('//li[@class="autor"]')->text();
				if(strpos($author, $nombre) !== false) {
					$content = $t->node('//*[@id="header-noticia"]/h3')->html();
					$content .= $t->node('//figure[@id="foto"]')->html();
					$content .= $t->node('//div[@itemprop="articleBody"]')->html();
					$content = preg_replace('#(?:<br\s*/?>\s*?)+#', '</p><p>', $content);
					$content = preg_replace('#src="/#', 'src="'.$url.'/', $content);
					$content = preg_replace('#href="/#', 'href="'.$url.'/', $content);
					$arts[] = new Article($link, $title, $author, $content);
				}
			}
		}
		return $arts;
	};
	$get_content = function(&$art) { return $art; };
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['ultimahora'] = function() {
	$url = "http://www.ultimahora.com";
	$data = new RSSMetadata("ultimahora", "Ultima Hora Columnistas", $url);
	$get_arts = function() use($url) {
		$arts = array();
		$authors = array("Alfredo Boccia Paz", "Estela Ruiz Díaz", "Andrés Colmán Gutiérrez");
		foreach($authors as $nombre) {
			$s = new Scrapper("$url/contenidos/resultado.html?text=".str_replace(" ","+",$nombre));
			foreach($s->query('//div[@class="result-obj"]//h3/a') as $a) {
				$link = $a->attr('href');
				$title = $a->text();
				$arts[] = new Article($link, $title, $nombre);
			}
		}
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content .= $s->node('//section[contains(@class, "note-body")]')->html();
		$art->content .= $s->node('//div[contains(@class, "news-detail-obj")]')->html();		
	};
	$data->add_getter($get_arts, $get_content);
	return $data;
};

$datas['levitsky'] = function() {
	$url = "http://larepublica.pe/persona/steven-levitsky";
	$data = new RSSMetadata("levitsky", "Steven Levitsky en La República", $url);
	$get_arts = function() use($url) {
		$arts = array();
		$s = new Scrapper($url);
		$a = $s->node('//a[@class="atm-title"]');
		$arts[] = new Article($a->attr('href'), $a->text(), "Steven Levitsky");
		return $arts;
	};
	$get_content = function(&$art) {
		$s = new scrapper($art->link);
		$art->content = $s->node('//div[@id="note-body"]')->html();
	};
	$data->add_getter($get_arts, $get_content);
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
			$art->content = str_replace(array("<", ">"), array("&lt;", "&gt;"), $art->content);
		}
		echo "<pre>\n";
		print_r($arts);
		echo "</pre>\n";
		foreach($arts as $link => &$art)
			$art->get_content = $get_content[$art->link];
		foreach($arts as $link => &$art) {
			$art->get_content();
			$art->get_content = null;
			$art->content = str_replace(array("<", ">"), array("&lt;", "&gt;"), $art->content);
			echo "<pre>\n";
			print_r($art);
			echo "</pre>\n";
			exit;
		}
	}
};

$cmd['rm'] = "Remove all articles.";
$func['rm'] = function($val) use($datas) {
	foreach(explode(',', $val) as $s) if(isset($datas[$s])) {
		$days = isset($_GET['last']) ? $_GET['last'] : 1000;
		$last_tms = time() - $days*24*60*60;
		$storage = new storage($s);
		$storage->conn->query("delete from $s where tms > $last_tms");
		echo "Erased all articles from $s<br><br>\n\n";
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
			echo "<div style='margin:auto;width:700px;'><h1>" . $datas[$name]()->title . "</h1>";
			$days = isset($_GET['days']) ? $_GET['days'] : 2;
			foreach($storage->last_articles($days) as $art)
				echo "<a href='retrieve.php?r=$name&link=" . urlencode($art->link) . "'>$art->title, by $art->author</a><br>";
			echo "</div>";
			continue;
		}
		$link = urldecode($_GET['link']);
		$arts = array($link => new Article($link));
		$storage->fetch_articles($arts);
		$art = $arts[$link];
		echo "<html><head><title>$art->title</title></head>";
		echo "<body><div style='margin:auto;width:700px;'><h1>$art->title</h1><p>Por $art->author. <a href='$link'>Link</a> <a href='retrieve.php?r=$name'>Volver</a></p>";
		echo "$art->content</div></body></html>";
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
