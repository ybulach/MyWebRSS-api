<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the unused feeds
	$delete = $mysql->query("DELETE FROM feeds WHERE feed_id NOT IN (SELECT DISTINCT feed_ref AS feed_id FROM user_feeds)");
	if(!$delete)
		send_error("Could not delete all the unused feeds");
	
	// Delete the feeds returning errors for to many time
	$date = time() - 60*60*24*$WRONG_FEED_TIME;
	$delete = $mysql->prepare("DELETE FROM feeds WHERE feed_error > 0 AND feed_error < :date");
	$delete->bindParam(":date", $date);
	
	if(!$delete->execute())
		send_error("Could not delete the wrong feeds");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
