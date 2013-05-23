<?php
require_once("lib.php");

try {
	// Check the arguments
	$user = check_token($_GET["token"]);
	
	if(!isset($_GET["feed"]) || (($_GET["feed"] != 0) && !check_arg($_GET["feed"], "#^[0-9]+$#", 1, 10)))
		throw new Exception("feed");
	$feed = $_GET["feed"];
	
	// Mark the articles as read
	$sql = "DELETE FROM user_articles WHERE user_ref=:user AND article_ref IN (SELECT DISTINCT article_id AS article_ref FROM articles)";
	if($feed)
		$sql = "DELETE FROM user_articles WHERE user_ref=:user AND article_ref IN (SELECT DISTINCT article_id AS article_ref FROM articles WHERE feed_ref=:feed)";
	
	$delete = $mysql->prepare($sql);
	if($feed)
		$delete->bindParam(":feed", $feed);
	$delete->bindParam(":user", $user);
	$delete->execute();
	
	if(!$delete)
		send_error("Could not delete all the unread articles");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
