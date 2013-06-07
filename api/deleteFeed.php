<?php
require_once("lib.php");

try {
	// Check the arguments
	$user = check_token($_GET["token"]);
	
	if(!isset($_GET["feed"]) || !check_arg($_GET["feed"], "#^[0-9]+$#", 1, 10))
		throw new Exception("feed");
	$feed = $_GET["feed"];
	
	// Delete the feed
	$delete = $mysql->prepare("DELETE FROM user_feeds WHERE user_ref=:user AND feed_ref=:feed");
	$delete->bindParam(":user", $user);
	$delete->bindParam(":feed", $feed);
	$delete->execute();
	
	if(!$delete)
		send_error("Could not delete the feed. Try again later");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
