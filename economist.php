<?php

include_once('engine.php');

set_time_limit(60*5);

function get_rubric($url) {
	$s = new scrapper('http://www.economist.com' . $url, array('silence'));
	return $s->node('//h1[@class="rubric"]')->text();
}

function main() {
	$date = isset($_GET['d']) ? $_GET['d'] : '';
	$s = new scrapper("http://www.economist.com/printedition/$date", array('silence'));

	foreach($s->query('//div[starts-with(@class,"section")]') as $div) {
		foreach($s->query('.//a[@class="node-link"]', $div->node) as $a) {
			$e = $s->html->createElement('p', get_rubric($a->attr('href')));
			$a->node->parentNode->appendChild($e);
			$url = 'http://www.economist.com' . $a->attr('href');
			$a->node->setAttribute('href', google_cache($url));
		}
	}
	
	echo $s->html->savehtml();
}

main();

?>
