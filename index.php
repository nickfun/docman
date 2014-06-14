<?php

require_once './bootstrap.php';

$db = new PDO("mysql:host=localhost;dbname=docman", "root", "root");

function fetchAll($sql, $db) {
	$r = $db->query($sql);
	if (!$r) {
		throw new \Exception("Could not execute sql $sql " . $db->errorInfo());
	}
	return $r->fetchAll(PDO::FETCH_ASSOC);
}

$list = fetchAll("select * from roles", $db);

foreach ($list as $row) {
	printf("Role %d - %s", $row['id'], $row['title']);
}