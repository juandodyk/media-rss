<?php

$region_csv = file_get_contents("region.csv");
$rs = array();
foreach(explode("\n", $region_csv) as $line) {
	$r = explode(",", $line); if(count($r) < 3) continue;
	if(!isset($rs[$r[1]])) $rs[$r[1]] = array();
	$rs[$r[1]][] = $r[2];
}
foreach($rs as $u => &$r) if(count($r) == 3) echo $u . " " . implode(", ", $r) . "\n";

foreach($rs as $u => &$r) $r = count($r);//implode(", ", $r);
$ret = array();
foreach($rs as $u => &$r) if($r == 3) $ret[] = $u;
echo implode(", ", $ret);
//print_r($rs);

?>