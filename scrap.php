<?php

function str_from($str, $a) {
	$i = strpos($str, $a);
	if($i === false) return '';
	return substr($str, $i + strlen($a));
}

function str_before($str, $a) {
	$i = strpos($str, $a);
	if($i === false) return $str;
	return substr($str, 0, $i);
}

function str_between($str, $a, $b) {
	$i = strpos($str, $a);
	if($i === false) return '';
	$i += strlen($a);
	$j = strpos($str, $b, $i);
	if($j === false) return substr($str, $i);
	return substr($str, $i, $j - $i);
}

function str_between_all($str, $a, $b) {
	$ret = array();
	$i = 0;
	while(strpos($str, $a, $i) !== false) {
		$i = strpos($str, $a, $i);
		$i += strlen($a);
		$j = strpos($str, $b, $i);
		if($j === false) {
			$ret[] = substr($str, $i);
			break;
		}
		$ret[] = substr($str, $i, $j - $i);
		$i = $j + strlen($b);
	}
	return $ret;
}

function starts_with($haystack, $needle) {
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function get_element_by_id($url, $id) {
	$html = new DOMDocument();
	@$html->loadHTMLFile($url);
	$element = $html->getElementById($id);
	return $html->saveHTML($element);
}

?>