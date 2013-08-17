<?php
require_once("lib.php");

try {
	// Check the arguments
	$result = check_token();
	
	if(!isset($_GET["feed"]) || (($_GET["feed"] != 0) && !check_arg($_GET["feed"], "#^[0-9]+$#", 1, 10)))
		throw new Exception("feed");
	$feed = $_GET["feed"];
	
	$articles_count = 20;
	if(isset($_GET["articles_count"])) {
		if(($_GET["articles_count"] != 0) && !check_arg($_GET["articles_count"], "#^[0-9]+$#", 1, 10))
			throw new Exception("articles_count");
		
		$articles_count = intval($_GET["articles_count"]);
	}
	
	$page = 0;
	if(isset($_GET["page"])) {
		if(($_GET["page"] != 0) && !check_arg($_GET["page"], "#^[0-9]+$#", 1, 10))
			throw new Exception("page");
		
		$page = $_GET["page"] * $articles_count;
	}
	
	// Get the articles of the feed
	$sql = "SELECT article_id as id, article_title as title, article_description as description, article_url as url, article_image as image, article_date as date, feeds.feed_title as feed FROM user_feeds INNER JOIN feeds ON user_feeds.feed_ref=feed_id INNER JOIN articles ON articles.feed_ref=feed_id WHERE user_ref=:user AND feed_id=:feed ORDER BY article_date DESC LIMIT :page,:max";
	
	// Get the unread articles, for the home page
	if(!$feed)
		$sql = "SELECT article_id as id, article_title as title, article_description as description, article_url as url, article_image as image, article_date as date, feeds.feed_title as feed FROM user_feeds INNER JOIN feeds ON user_feeds.feed_ref=feed_id INNER JOIN articles ON articles.feed_ref=feed_id INNER JOIN user_articles ON article_id=article_ref AND user_feeds.user_ref=user_articles.user_ref WHERE user_feeds.user_ref=:user ORDER BY article_date DESC LIMIT :page,:max";
	
	$select = $mysql->prepare($sql);
	$select->bindParam(":user", $user);
	if($feed)
		$select->bindParam(":feed", $feed);
	$select->bindParam(":page", $page, PDO::PARAM_INT);
	$select->bindParam(":max", $articles_count, PDO::PARAM_INT);
	
	if(!$select->execute())
		throw new Exception("Could not list articles. Try again later");
	
	$result = $select->fetchAll(PDO::FETCH_ASSOC);
	
	// Add the feed name
	if($feed) {
		if(count($result) > 0)
			$json_result["feed"] = $result[0]["feed"];
		else {
			// Get the name from the database
			$select = $mysql->prepare("SELECT feeds.feed_title as feed FROM user_feeds INNER JOIN feeds ON user_feeds.feed_ref=feed_id WHERE user_ref=:user AND feed_ref=:feed");
			$select->bindParam(":user", $user);
			$select->bindParam(":feed", $feed);
			$select->execute();
			
			if($feed_title = $select->fetch())
				$json_result["feed"] = $feed_title["feed"];
			else
				throw new Exception("feed");
		}
	}
	
	$json_result["result"] = array();
	foreach($result as $article) {
		$article["date"] = date("d-m-Y H:i:s", $article["date"]);
		$article["title"] = htmlspecialchars($article["title"]);
		$article["feed"] = htmlspecialchars($article["feed"]);
		
		if($feed) {
			// Check the status (read or not)
			$select = $mysql->prepare("SELECT article_ref FROM user_articles WHERE user_ref=:user AND article_ref=:article");
			$select->bindParam(":user", $user);
			$select->bindParam(":article", $article["id"]);
			$select->execute();
			$new = $select->fetch();
			
			if($new)
				$article["status"] = "new";
		}
		// The home page only shows unread articles
		else
			$article["status"] = "new";
		
		array_push($json_result["result"], $article);
	}
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
