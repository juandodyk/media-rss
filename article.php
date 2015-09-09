<?php

include_once('storage.php');

$name = $_GET['name'];
$storage = new Storage($name);

if(!isset($_GET['link'])) {
	$arts = $storage->last_articles();
	foreach($arts as $art) {
		echo '<a href="article.php?name=' . $name . '&link=' . urlencode($art->link) . '">';
		echo "$art->author - $art->title</a><br>";
	}
	exit;
}

$link = urldecode($_GET['link']);

$arts = array($link => new Article($link));
$storage->fetch_articles($arts);
$art = $arts[$link];
echo "<html><head><title>$art->title</title></head>";
echo "<body><h1>$art->title</h1><p>Por $art->author. <a href='$link'>Link</a> <a href='article.php?name=$name'>Volver</a></p>";
echo "$art->content</body></html>";

?>
