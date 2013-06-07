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
	
	// Add the unread articles count
	for($i = 0; $i < $count($json_result["result"]); $i++) {
		$json_result["result"][$i]["unread"] = 0;
		
		$select = $mysql->prepare("SELECT COUNT(feed_ref) as unread FROM user_articles INNER JOIN articles ON article_id=article_ref WHERE user_ref=:user AND feed_ref=:feed");
		$select->bindParam(":user", $user);
		$select->bindParam(":feed", $json_result["result"][$i]["id"]);
		
		if($select->execute()) {
			$unread = $select->fetch();
			
			if($unread) {
				$unread = $unread["unread"];
				$json_result["result"][$i]["unread"] = $unread;
			}
		}
	}
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
