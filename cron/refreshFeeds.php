<?php
// Script to run every minutes
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
	// List the older feeds (that hasn't been loaded since x minutes)
	$date = time() - 60 * $REFRESH_INTERVAL;
	$select = $mysql->prepare("SELECT feed_id, feed_url, feed_date FROM feeds WHERE feed_date < :date ORDER BY feed_date ASC");
	$select->bindParam(":date", $date);
	
	if(!$select->execute())
		send_error("Could not list the feeds");
	
	// Handle each feed
	$max_age = time() - 60*60*24*$MAX_ARTICLE_AGE;
	while($result = $select->fetch()) {
		$feed_id = $result["feed_id"];
		$feed_url = $result["feed_url"];
		$feed_date = $result["feed_date"];
		
		send_warning($feed_url);
		
		// The execution of this script can be long, and many instances can be running at the same time
		// So we check again the feed date, to see if another script has modified it
		$select2 = $mysql->prepare("SELECT feed_id, feed_date FROM feeds WHERE feed_id=:feed");
		$select2->bindParam(":feed", $feed_id);
		
		$execution = $select2->execute();
		$feed = $select2->fetch();
		if(!$execution | !$feed) {
			send_warning("Could not check date");
			continue;
		}
		
		send_warning($feed["feed_date"]);
		if($feed["feed_date"] >= $date) {
			send_warning("Already checked");
			continue;
		}
		
		// Get the XML file and check RSS
		$dom = new DomDocument();
		if(!$dom->load($feed_url) || (!$dom->getElementsByTagName("rss")->length && !$dom->getElementsByTagName("feed")->length)) {
			// Change the date, to only try again in x*2 minutes
			$date = time() + 60 * $REFRESH_INTERVAL * 2;
			$sql = "UPDATE feeds SET feed_date=".$date;
			
			// Change the title if the feed has never been loaded
			if($feed_date == 0)
				$sql .= ", feed_title='**NOT AN RSS FEED**', feed_description='This feed returns errors for more than 2 hours'";
			
			$sql .= ", feed_error=1 WHERE feed_id=:id";
			
			$update = $mysql->prepare($sql);
			$update->bindParam(":id", $feed_id);
			
			if(!$update->execute())
				send_warning("Could not update the feed ".$feed_id);
			
			continue;
		}
		
		// Refresh the feed properties
		$feed_title = get_xml_value($dom->getElementsByTagName("title"), "No title");
		$feed_description = get_xml_value($dom->getElementsByTagName("description"), "No description");
		
		// Get all the articles in the RSS
		$articles = $dom->getElementsByTagName("item");
		if(!$articles->length)
			$articles = $dom->getElementsByTagName("entry");
		
		foreach($articles as $article) {
			// Get the values
			$article_title = get_xml_value($article->getElementsByTagName("title"), "No title");
			$article_image = get_xml_value($article->getElementsByTagName("image"), "");
			
			// <pubDate>
			$article_date = get_xml_value($article->getElementsByTagName("pubDate"), "");
			if(!$article_date)
				// <a10:updated>
				$article_date = get_xml_value($article->getElementsByTagNameNS("http://www.w3.org/2005/Atom", "updated"), "");
			if(!$article_date)
				// <updated>
				$article_date = get_xml_value($article->getElementsByTagName("updated"), "");
			
			if(!$article_date)
				$article_date = time();
			else
				$article_date = strtotime($article_date);
			
			// Don't handle old articles
			if($article_date && ($article_date < $max_age))
				continue;
			
			// <guid>
			$article_guid = get_xml_value($article->getElementsByTagName("guid"), "");
			if(!$article_guid)
				// <id>
				$article_guid = get_xml_value($article->getElementsByTagName("id"), "");
			
			// <link>
			$url = get_xml_item($article->getElementsByTagName("link"));
			if($url) {
				$article_url = $url->nodeValue;
				if(!$article_url && $url->hasAttribute("href"))
					// <link href="">
					$article_url = $url->getAttribute("href");
			}
			
			if(!$article_guid) {
				if($article_url)
					$article_guid = $article_url;
				else {
					send_warning("error > $article_title = No GUID or URL");
					continue;
				}
			}
			
			// <description>
			$article_description = get_xml_value($article->getElementsByTagName("description"), "");
			if(!$article_description)
				// <content>
				$article_description = get_xml_value($article->getElementsByTagName("content"), "");
			
			// Check if the article exists
			$select2 = $mysql->prepare("SELECT article_id FROM articles WHERE article_guid=:guid AND feed_ref=:feed");
			$select2->bindParam(":guid", $article_guid);
			$select2->bindParam(":feed", $feed_id);
			$select2->execute();
			
			if($select2->fetch())
				continue;
			
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
		$date = time();
		$update = $mysql->prepare("UPDATE feeds SET feed_title=:title, feed_description=:description, feed_date=:date, feed_error=0 WHERE feed_id=:id");
		$update->bindParam(":title", $feed_title);
		$update->bindParam(":description", $feed_description);
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
