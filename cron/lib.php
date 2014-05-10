<?php
require_once("../config.inc.php");

///////////////////////////////////////////////////////////////////////////////
// Send a warning result
function send_warning($warning) {
	echo "[".time()."] ".$warning."\n";
}

///////////////////////////////////////////////////////////////////////////////
// Send an error result
function send_error($error) {
	send_warning($error);
	die();
}

///////////////////////////////////////////////////////////////////////////////
// Do not display errors
//error_reporting(0);

// Connect to MySQL
try {
	$mysql = new PDO("mysql:host=".$mysql_host.";dbname=".$mysql_base, $mysql_user, $mysql_pass);
	$mysql->exec("SET CHARACTER SET utf8");
}
catch(Exception $e) {
	send_error("Server error: ".$e->getMessage());
}

?>
