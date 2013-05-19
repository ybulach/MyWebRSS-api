<?php
require_once("lib.php");

try {
	// Check the arguments
	$user = check_token($_GET["token"]);
	
	if(!isset($_GET["article"]) || !check_arg($_GET["article"], "#^[0-9]+$#", 1, 10))
		throw new Exception("article");
	$article = $_GET["article"];
	
	// Get the article
	$select = $mysql->prepare("SELECT article_id as id, article_title as title, article_description as description, article_url as url, article_image as image, article_date as date FROM user_feeds INNER JOIN feeds ON user_feeds.feed_ref=feed_id INNER JOIN articles ON articles.feed_ref=feed_id WHERE article_id=:article AND user_ref=:user");
	$select->bindParam(":article", $article);
	$select->bindParam(":user", $user);
	
	if(!$select->execute())
		throw new Exception("Could not get article content. Try again later");
	
	$result = $select->fetch(PDO::FETCH_ASSOC);
	if(!$result)
		throw new Exception("article");
	
	$result["date"] = date("d-m-Y H:i:s", $article["date"]);
	$json_result["result"] = $result;
	
	// Mark the article as read
	$delete = $mysql->prepare("DELETE FROM user_articles WHERE article_ref=:article AND user_ref=:user");
	$delete->bindParam(":article", $article);
	$delete->bindParam(":user", $user);
	$delete->execute();
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
