<?php
require_once("lib.php");

try {
	// Check the arguments
	if(!isset($_GET["token"]) || !check_arg($_GET["token"], "#^[a-z0-9]+$#", 40, 40))
		throw new Exception("token");
	
	if(!isset($_GET["feed"]) || !check_arg($_GET["feed"], "#^http\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(/\S*)?$#", 7, 255))
		throw new Exception("feed");
	
	// Try to get the user ID
	$select = $mysql->prepare("SELECT user_ref FROM tokens WHERE token_id=:token LIMIT 1");
	$select->bindParam(":token", $_GET["token"]);
	$success = $select->execute();
	if(!$success)
		throw new Exception("Could not get the account informations. Try again later");
	
	$result = $select->fetch();
	if(!$result)
		throw new Exception("token");
	
	$user = $result["user_ref"];
	
	// Check if the feed exists
	$select = $mysql->prepare("SELECT feed_id FROM feeds WHERE feed_url=:feed LIMIT 1");
	$select->bindParam(":feed", $_GET["feed"]);
	
	if(!$select->execute())
		throw new Exception("Could not check the feed. Try again later");
	
	$result = $select->fetch();
	if(!$result) {
		// Try to add the new feed
		$insert = $mysql->prepare("INSERT INTO feeds(feed_id, feed_url, feed_title, feed_description, feed_date) VALUES(NULL, :feed, 'Loading soon', 'Your new feed will load soon', 0)");
		$insert->bindParam(":feed", $_GET["feed"]);
		
		if(!$insert->execute())
			throw new Exception("Could not add the feed. Try again later");
		
		// Get the feed ID
		$select = $mysql->prepare("SELECT feed_id FROM feeds WHERE feed_url=:feed LIMIT 1");
		$select->bindParam(":feed", $_GET["feed"]);
		
		if(!$select->execute())
			throw new Exception("No account found. Check the email and the password");
		
		$result = $select->fetch();
		if(!$result)
			throw new Exception("Could not get the new feed informations. Try again later");
	}
	
	$feed = $result["feed_id"];
	$json_result["id"] = $feed;
	
	// Try to add the feed to the user
	$insert = $mysql->prepare("INSERT INTO user_feeds(user_ref, feed_ref) VALUES(:user, :feed)");
	$insert->bindParam(":user", $user);
	$insert->bindParam(":feed", $feed);
	
	if(!$insert->execute())
		throw new Exception("Could not add the feed to your account. Maybe is it already added");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
