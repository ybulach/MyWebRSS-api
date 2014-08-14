<?php
// Script to run every minutes
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");
require_once("lib.feed.php");

try {
	// List the older feeds (that hasn't been loaded since x minutes)
	$date = time() - 60 * $REFRESH_INTERVAL;
	$select = $mysql->prepare("SELECT feed_id, feed_url, feed_date, feed_error FROM feeds WHERE feed_date < :date ORDER BY feed_date ASC");
	$select->bindParam(":date", $date);
	
	if(!$select->execute())
		send_error("Could not list the feeds");
	
	// Handle each feed
	$max_age = time() - 60*60*24*$MAX_ARTICLE_AGE;
	while($result = $select->fetch()) {
		$feed_id = $result["feed_id"];
		$feed_url = $result["feed_url"];
		$feed_date = $result["feed_date"];
		$feed_error = $result["feed_error"];
		
		send_warning($feed_url);
		
		// The execution of this script can be long, and many instances can be running at the same time
		// So we check again the feed date, to see if another script has modified it
		$select2 = $mysql->prepare("SELECT feed_id, feed_date FROM feeds WHERE feed_id=:feed");
		$select2->bindParam(":feed", $feed_id);
		
		$execution = $select2->execute();
		$feed_db = $select2->fetch();
		if(!$execution | !$feed_db) {
			send_warning("Could not check date");
			continue;
		}
		
		send_warning($feed_db["feed_date"]);
		if($feed_db["feed_date"] >= $date) {
			send_warning("Already checked");
			continue;
		}
		
		// Change the date of the feed to prevent concurrent update (used for really big and slow feeds)
		$date = time();
		$update = $mysql->prepare("UPDATE feeds SET feed_date=:date WHERE feed_id=:id");
		$update->bindParam(":date", $date);
		$update->bindParam(":id", $feed_id);
		
		if(!$update->execute())
		{
			send_warning("Could not update the feed ".$feed_id);
			continue;
		}
		
		// Get the Feed
		$feedloader = new FeedLoader();
		if(!$feedloader->load($feed_url)) {
			// Change the date, to only try again in x*2 minutes
			$date = time() + 60 * $REFRESH_INTERVAL * 2;
			$sql = "UPDATE feeds SET feed_date=".$date;
			
			// Change the title if the feed has never been loaded
			if($feed_date == 0)
				$sql .= ", feed_title='**NOT AN RSS FEED**', feed_description='This feed returns errors for more than 2 hours'";
			
			// Set the time of the error (used to delete feeds that are wrong for to long)
			if($feed_error == 0)
				$sql .= ", feed_error=".time();
			
			$sql .= " WHERE feed_id=:id";
			
			$update = $mysql->prepare($sql);
			$update->bindParam(":id", $feed_id);
			
			if(!$update->execute())
				send_warning("Could not update the feed ".$feed_id);
			
			continue;
		}
		
		// Get all the articles
		$articles = $feedloader->getItems();
		foreach($articles as $article) {
			// Don't handle old articles
			if($article->date && ($article->date < $max_age))
				continue;
			
			// Get image
			$article_image = "";
			if($article->enclosure && (strpos($article->enclosure->type, "image") === 0))
				$article_image = $article->enclosure->url;
			
			// Check if the article exists
			$select2 = $mysql->prepare("SELECT article_id FROM articles WHERE article_guid=:guid AND feed_ref=:feed");
			$select2->bindParam(":guid", $article->guid);
			$select2->bindParam(":feed", $feed_id);
			$select2->execute();
			
			if($select2->fetch())
				continue;
			
			// Add the article
			$insert = $mysql->prepare("INSERT INTO articles(article_id, article_url, feed_ref, article_guid, article_title, article_description, article_image, article_date) VALUES(NULL, :url, :feed, :guid, :title, :description, :image, :date)");
			$insert->bindParam(":url", $article->url, PDO::PARAM_STR);
			$insert->bindParam(":feed", $feed_id, PDO::PARAM_INT);
			$insert->bindParam(":guid", $article->guid, PDO::PARAM_STR);
			$insert->bindParam(":title", $article->title, PDO::PARAM_STR);
			$insert->bindParam(":description", $article->description, PDO::PARAM_STR);
			$insert->bindParam(":image", $article_image, PDO::PARAM_STR);
			$insert->bindParam(":date", $article->date, PDO::PARAM_INT);
			if(!$insert->execute()) {
				send_warning("Could not insert the article ".$article->guid);
				continue;
			}
			
			// Get the article ID
			$select2 = $mysql->prepare("SELECT article_id FROM articles WHERE article_guid=:guid AND feed_ref=:feed");
			$select2->bindParam(":guid", $article->guid);
			$select2->bindParam(":feed", $feed_id);
			$select2->execute();
			
			$article_id = $select2->fetch();
			if($article_id) {
				$article_id = $article_id["article_id"];
				
				// Unread the article for every users
				$select2 = $mysql->prepare("SELECT user_ref FROM user_feeds WHERE feed_ref=:feed");
				$select2->bindParam(":feed", $feed_id);
				$select2->execute();
				
				while($user = $select2->fetch()) {
					$insert = $mysql->prepare("INSERT INTO user_articles(user_ref, article_ref) VALUES(:id, :article)");
					$insert->bindParam(":id", $user["user_ref"]);
					$insert->bindParam(":article", $article_id);
					$insert->execute();
				}
			}
			
			send_warning("added > {$article->title} ({$article->guid}) {$article->date}");
		}
		
		// Get the feed infos
		$feed = $feedloader->getFeed();
		
		// Change the date of the feed
		$date = time();
		$update = $mysql->prepare("UPDATE feeds SET feed_title=:title, feed_description=:description, feed_date=:date, feed_error=0 WHERE feed_id=:id");
		$update->bindParam(":title", $feed->title);
		$update->bindParam(":description", $feed->description);
		$update->bindParam(":date", $date);
		$update->bindParam(":id", $feed_id);
		
		if(!$update->execute())
			send_warning("Could not update the feed ".$feed_id);
	}
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
