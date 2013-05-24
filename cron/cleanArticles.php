<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the old articles
	$date = time() - 60*60*24*$MAX_ARTICLE_AGE;
	$delete = $mysql->prepare("DELETE FROM articles WHERE article_date < :date");
	$delete->bindParam(":date", $date);
	
	if(!$delete->execute())
		send_error("Could not delete all the old articles");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
