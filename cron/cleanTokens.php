<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the expired Tokens (over 30 days old)
	$date = time();
	$delete = $mysql->prepare("DELETE FROM tokens WHERE token_date < :date");
	$delete->bindParam(":date", $date);
	
	if(!$delete->execute())
		send_error("Could not delete all the Tokens");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
