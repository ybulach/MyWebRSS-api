<?php
require_once("lib.php");

try {
	// Check the arguments
	if(!isset($_GET["token"]) || !check_arg($_GET["token"], "#^[a-z0-9]+$#", 40, 40))
		throw new Exception("token");
	
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
	
	// Get the feeds
	$select = $mysql->prepare("SELECT feed_id, feed_title, feed_description FROM user_feeds INNER JOIN feeds ON feed_id=feed_ref WHERE user_ref=:user");
	$select->bindParam(":user", $user);
	
	if(!$select->execute())
		throw new Exception("Could not list feed. Try again later");
	
	while($result = $select->fetch()) {
		$feed["id"] = $result["feed_id"];
		$feed["title"] = $result["feed_title"];
		$feed["description"] = $result["feed_description"];
		$json_result[$result["feed_id"]] = $feed;
	}
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
