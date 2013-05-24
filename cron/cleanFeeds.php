<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the unused feeds
	$delete = $mysql->query("DELETE FROM feeds WHERE feed_id NOT IN (SELECT DISTINCT feed_ref AS feed_id FROM user_feeds)");
	
	if(!$delete)
		send_error("Could not delete all the unused feeds");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
