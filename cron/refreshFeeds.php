<?php
// Script to run every 5 minutes
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

// Get the element value or return $default_value
function get_xml_item($element) {
	if(!$element->length)
		return null;
	
	return $element->item(0);
}

function get_xml_value($element, $default_value) {
	$value = get_xml_item($element);
	return $value ? $value->nodeValue : $default_value;
}

try {
	// List the older feeds (that hasn't been loaded since 5 minutes)
	$date = time() - 60*5;
	$select = $mysql->prepare("SELECT feed_id, feed_url, feed_date FROM feeds WHERE feed_date < :date ORDER BY feed_date ASC");
	//$select = $mysql->prepare("SELECT feed_id, feed_url, feed_date FROM feeds ORDER BY feed_date ASC");
	$select->bindParam(":date", $date);
	
	if(!$select->execute())
		send_error("Could not list the feeds");
	
	// Handle each feed
	while($result = $select->fetch()) {
		$feed_id = $result["feed_id"];
		$feed_url = $result["feed_url"];
		$feed_date = $result["feed_date"];
		
		send_warning($feed_url);
		
		// Get the XML file
		$dom = new DomDocument();
		$dom->load($feed_url);
		
		// Check RSS
		if(!$dom->getElementsByTagName("rss")->length && !$dom->getElementsByTagName("feed")->length)
			continue;
		
		// Refresh the feed properties
		$feed_title = get_xml_value($dom->getElementsByTagName("title"), "No title");
		$feed_description = get_xml_value($dom->getElementsByTagName("description"), "No description");
		$date = get_xml_value($dom->getElementsByTagName("lastBuildDate"), "");
		if(!$date)
			$date = get_xml_value($dom->getElementsByTagName("updated"), "");
		$date = strtotime($date);
		
		// Refresh every 5 minutes if the RSS doesn't give a date
		if(!$date) {
			$date = time();
			$feed_date += 60*5;
		}
		
		// Only refresh if it has been rebuilded
		if($date <= $feed_date)
			continue;
		$feed_date = time();
		
		// Get the last article GUID
		$select2 = $mysql->prepare("SELECT article_guid FROM articles WHERE feed_ref=:id ORDER BY article_id DESC");
		$select2->bindParam(":id", $feed_id);
		
		if(!$select2->execute())
			send_warning("Could not get the last article for ".$id);
		
		$result2 = $select2->fetch();
		$guid = $result2["article_guid"];
		
		// Get all the articles in the RSS
		$articles = $dom->getElementsByTagName("item");
		if(!$articles->length)
			$articles = $dom->getElementsByTagName("entry");
		
		foreach($articles as $article) {
			// Only add new articles
			$article_guid = get_xml_value($article->getElementsByTagName("guid"), "");
			if(!$article_guid)
				$article_guid = get_xml_value($article->getElementsByTagName("id"), "");
			
			if($article_guid == $guid)
				break;
			
			// Get the values
			$article_title = get_xml_value($article->getElementsByTagName("title"), "No title");
			$article_image = get_xml_value($article->getElementsByTagName("image"), "");
			
			// <link>
			$url = get_xml_item($article->getElementsByTagName("link"));
			if($url) {
				$article_url = $url->nodeValue;
				if(!$article_url && $url->hasAttribute("href"))
					// <link href="">
					$article_url = $url->hasAttribute("href");
			}
			
			// <description>
			$article_description = get_xml_value($article->getElementsByTagName("description"), "");
			if(!$article_description)
				// <content>
				$article_description = get_xml_value($article->getElementsByTagName("content"), "");
			
			// <pubDate>
			$article_date = get_xml_value($article->getElementsByTagName("pubDate"), "");
			if(!$article_date)
				// <a10:updated>
				$article_date = get_xml_value($article->getElementsByTagNameNS("http://www.w3.org/2005/Atom", "updated"), "");
			if(!$article_date)
				// <updated>
				$article_date = get_xml_value($article->getElementsByTagName("updated"), "");
			
			if($article_date)
				$article_date = strtotime($article_date);
			
			// Check if the article exists
			$select2 = $mysql->prepare("SELECT article_id FROM articles WHERE article_guid=:guid AND feed_ref=:feed");
			$select2->bindParam(":guid", $article_guid);
			$select2->bindParam(":feed", $feed_id);
			$select2->execute();
			
			if($select2->fetch())
				break;
			
			// Add the article
			$insert = $mysql->prepare("INSERT INTO articles(article_id, article_url, feed_ref, article_guid, article_title, article_description, article_image, article_date) VALUES(NULL, :url, :feed, :guid, :title, :description, :image, :date)");
			$insert->bindParam(":url", $article_url, PDO::PARAM_STR);
			$insert->bindParam(":feed", $feed_id, PDO::PARAM_INT);
			$insert->bindParam(":guid", $article_guid, PDO::PARAM_STR);
			$insert->bindParam(":title", $article_title, PDO::PARAM_STR);
			$insert->bindParam(":description", $article_description, PDO::PARAM_STR);
			$insert->bindParam(":image", $article_image, PDO::PARAM_STR);
			$insert->bindParam(":date", $article_date, PDO::PARAM_INT);
			$insert->execute();
			
			// Get the article ID
			$select2 = $mysql->prepare("SELECT article_id FROM articles WHERE article_guid=:guid AND feed_ref=:feed");
			$select2->bindParam(":guid", $article_guid);
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
			
			send_warning("added > $article_title ($article_guid) $article_date");
		}
		
		// Change the date of the feed
		$update = $mysql->prepare("UPDATE feeds SET feed_title=:title, feed_description=:description, feed_date=:date WHERE feed_id=:id");
		$update->bindParam(":title", $feed_title);
		$update->bindParam(":description", $feed_description);
		$update->bindParam(":date", $feed_date);
		$update->bindParam(":id", $feed_id);
		
		if(!$update->execute())
			send_warning("Could not update the feed ".$feed_id);
	}
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
