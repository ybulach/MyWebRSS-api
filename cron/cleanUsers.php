<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the inactive users
	$date = time() - 60*60*24*$MAX_INACTIVE_USER_AGE;
	$delete = $mysql->prepare("DELETE FROM users WHERE user_lastlogin < :date");
	$delete->bindParam(":date", $date);
	
	if(!$delete->execute())
		send_error("Could not delete all the inactive users");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
