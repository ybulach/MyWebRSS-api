<?php
require_once("lib.php");

try {
	// Check the arguments
	$user = check_token($_GET["token"]);
	
	// Get the feeds
	$select = $mysql->prepare("SELECT feed_id as id, feed_title as title, feed_description as description FROM user_feeds INNER JOIN feeds ON feed_id=feed_ref WHERE user_ref=:user ORDER BY feed_title ASC");
	$select->bindParam(":user", $user);
	
	if(!$select->execute())
		throw new Exception("Could not list feed. Try again later");
	
	$json_result["result"] = $select->fetchAll(PDO::FETCH_ASSOC);
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
