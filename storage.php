<?php

class Article {
	public $link, $title, $author, $content;
	public $tms, $saved = false;
	public $get_content;
	
	function __construct($link, $title = '', $author = '', $content = '') {
		$this->link = $link;
		$this->title = $title;
		$this->author = $author;
		$this->content = $content;
		$this->tms = time();
		$get_content = function(&$art) { return ''; };
	}
	
	function get_content() { $f = $this->get_content; $f($this); }
}

class Storage {
	public $conn;
	public $table_name;
	
	function __construct($table_name = "") {
		$this->table_name = $table_name;
		$this->connect();
	}
	
	function __destruct() {
		$this->conn->close();
	}
	
	function connect() {
		$servername = "localhost";
		$username = "998843";
		$password = "pterosaurio1";
		$dbname = "998843";
		$this->conn = new mysqli($servername, $username, $password, $dbname);
		if ($this->conn->connect_error)
			echo "Connection failed: " . $this->conn->connect_error;
	}
	
	function create_table() {
		$link_index = $this->table_name . "_link_index";
		$tms_index = $this->table_name . "_tms_index";
		$sql =
			"CREATE TABLE $this->table_name (
				link VARCHAR(256) PRIMARY KEY,
				tms INTEGER NOT NULL,
				title TEXT,
				author TEXT,
				content LONGTEXT
			)";
		$res = $this->conn->query($sql);
		$res = $res && $this->conn->query("CREATE INDEX $link_index ON $this->table_name (link)");
		$res = $res && $this->conn->query("CREATE INDEX $tms_index ON $this->table_name (tms)");
		if(!$res) die("Table $this->table_name creation failed." . $this->conn->error);
	}
	
	function erase_table() {
		$sql = "DROP TABLE $this->table_name";
		if(!$this->conn->query($sql))
			die("Table $this->table_name destruction failed: " . $this->conn->error);
	}
	
	function sql_str(&$s) {
		return "'" . $this->conn->real_escape_string($s) . "'";
	}
	
	function add_article($art) {
		$link = $this->sql_str($art->link);
		$title = $this->sql_str($art->title);
		$author = $this->sql_str($art->author);
		$content = $this->sql_str($art->content);
		$sql =
			"INSERT INTO $this->table_name (link, tms, title, author, content)
			 VALUES ($link, $art->tms, $title, $author, $content)";
		$i = 0;
		while(!$this->conn->query($sql) && $i++ < 3)
			$this->connect();
		if($i >= 3) echo "FAIL ADD: $sql\n" . $this->conn->error . "\n\n";
	}
	
	function fetch_articles(&$arts) {
		$sql = "SELECT * FROM $this->table_name WHERE link in (";
		$first = true;
		foreach($arts as $link => $art) {
			if($first) $first = false;
			else $sql .= ", ";
			$sql .= $this->sql_str($link);
		}
		$sql .= ")";
		$res = $this->conn->query($sql);
		if(!$res) {
			echo "FAIL FETCH: $sql\n" . $this->conn->error . "\n\n";
			return;
		}
		foreach($res as $art) {
			$arts[$art['link']]->title = $art['title'];
			$arts[$art['link']]->tms = $art['tms'];
			$arts[$art['link']]->author = $art['author'];
			$arts[$art['link']]->content = $art['content'];
			$arts[$art['link']]->saved = true;
		}
	}
	
	function clean_old($days) {
		$last_tms = time() - $days*24*60*60;
		$sql = "DELETE FROM $this->table_name WHERE tms < $last_tms";
		if(!$this->conn->query($sql))
			echo "FAIL CLEAN: $sql\n\n" . $this->conn->error . "\n\n";
	}
	
	function show_articles() {
		$res = $this->conn->query("select * from $this->table_name");
		if(!$res) echo "FAIL SHOW $sql\n" . $this->conn->error . "\n\n";
		foreach($res as $art) print_r($art);
	}
	
	function last_articles($days = 2) {
		$last_tms = time() - $days*24*60*60;
		$sql = "SELECT link, title, author FROM $this->table_name WHERE tms >= $last_tms";
		$res = $this->conn->query($sql);
		if(!$res) echo "FAIL SHOW $sql\n" . $this->conn->error . "\n";
		$arts = array();
		foreach($res as $art)
			$arts[] = new article($art['link'], $art['title'], $art['author']);
		return $arts;
	}
}

?>
