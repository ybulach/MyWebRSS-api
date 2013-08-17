<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the expired Tokens
	$date = time() - 60*60*24*$MAX_TOKEN_AGE;
	$delete = $mysql->prepare("DELETE FROM tokens WHERE token_date < :date");
	$delete->bindParam(":date", $date);
	
	if(!$delete->execute())
		send_error("Could not delete the old Tokens");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
