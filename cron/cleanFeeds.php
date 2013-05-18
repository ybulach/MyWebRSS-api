<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the unused feeds
	$date = time();
	$delete = $mysql->query("DELETE FROM feeds");
	
	if(!$delete)
		send_error("Could not delete all the unused feeds");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
