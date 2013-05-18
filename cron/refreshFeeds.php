<?php
// Script to run every 5 minutes
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

// Get the element value or return $default_value
function get_xml_value($element, $default_value) {
	if(!$element->length)
		return $default_value;
	
	return $element->item(0)->nodeValue;
}

try {
	// List the older feeds
	$date = time() - 60*5;
	//$select = $mysql->prepare("SELECT feed_id, feed_url FROM feeds WHERE feed_date < :date ORDER BY feed_date ASC");
	$select = $mysql->prepare("SELECT feed_id, feed_url, feed_date FROM feeds ORDER BY feed_date ASC");
	$select->bindParam(":date", $date);
	
	if(!$select->execute())
		send_error("Could not list the feeds");
	
	// Handle each feed
	while($result = $select->fetch()) {
		$feed_id = $result["feed_id"];
		$feed_url = $result["feed_url"];
		$feed_date = $result["feed_date"];
		
		// Get the XML file
		$dom = new DomDocument();
		$dom->load($feed_url);
		
		// Check RSS
		if(!$dom->getElementsByTagName("rss")->length)
			continue;
		
		// Refresh the feed properties
		$feed_title = get_xml_value($dom->getElementsByTagName("title"), "No title");
		$feed_description = get_xml_value($dom->getElementsByTagName("description"), "No description");
		$date = strtotime(get_xml_value($dom->getElementsByTagName("lastBuildDate"), 0));
		
		// Only refresh if it has been rebuilded
		if($date <= $feed_date)
			continue;
		$feed_date = $date;
		
		$update = $mysql->prepare("UPDATE feeds SET feed_title=:title, feed_description=:description, feed_date=:date WHERE feed_id=:id");
		$update->bindParam(":title", $feed_title);
		$update->bindParam(":description", $feed_description);
		$update->bindParam(":date", $feed_date);
		$update->bindParam(":id", $feed_id);
		
		if(!$update->execute()) {
			send_warning("Could not update the feed ".$id);
			continue;
		}
		
		// Get the last article GUID
		$select2 = $mysql->prepare("SELECT article_guid FROM articles WHERE feed_ref=:id ORDER BY article_id DESC");
		$select2->bindParam(":id", $feed_id);
		
		if(!$select2->execute())
			send_warning("Could not get the last article for ".$id);
		
		$result2 = $select2->fetch();
		$guid = $result2["article_guid"];
		
		// Get all the articles in the RSS
		$articles = $dom->getElementsByTagName("item");
		foreach($articles as $article) {
			// Only add new articles
			// TODO: Handle a10:updated stuff
			$article_guid = get_xml_value($article->getElementsByTagName("guid"), $guid);
			if($article_guid == $guid)
				break;
			
			// Get the values
			$article_url = get_xml_value($article->getElementsByTagName("url"), "");
			$article_title = get_xml_value($article->getElementsByTagName("title"), "No title");
			$article_description = get_xml_value($article->getElementsByTagName("description"), "No description");
			$article_image = get_xml_value($article->getElementsByTagName("image"), "");
			$article_date = strtotime(get_xml_value($article->getElementsByTagName("pubDate"), 0));
			
			// Add the article
			$insert = $mysql->prepare("INSERT INTO articles(article_id, article_url, feed_ref, article_guid, article_title, article_description, article_image, article_date) VALUES(NULL, :url, :feed, :guid, :title, :description, :image, :date)");
			$insert->bindParam(":url", $article_url);
			$insert->bindParam(":feed", $feed_id);
			$insert->bindParam(":guid", $article_guid);
			$insert->bindParam(":title", $article_title);
			$insert->bindParam(":description", $article_description);
			$insert->bindParam(":image", $article_image);
			$insert->bindParam(":date", $article_date);
			
			$insert->execute();
		}
	}
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
