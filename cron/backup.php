<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the unused feeds
	$date = date("Y-m-d_H-i-s");
	exec($mysqldump." --user=".$mysql_user." --password=".$mysql_pass." --host=".$mysql_host." ".$mysql_base." > backups/".$date);
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
