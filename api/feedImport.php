<?php
require_once("lib.php");

try {
	// Check the arguments
	$result = check_token();
	
	// Get the XML file and check RSS
	$dom = new DomDocument();
	if(!$dom->loadXML($_POST["file"]) || !$dom->getElementsByTagName("opml")->length)
		throw new Exception("file");
	
	$feeds = $dom->getElementsByTagName("outline");
	
	$success = 0;
	foreach($feeds as $feed) {
		// Check the URL
		if(!$feed->hasAttribute("xmlUrl"))
			continue;
		$url = $feed->getAttribute("xmlUrl");
		
		if(!check_arg($url, "#^(http|https)\://[a-zA-Z0-9\-\.]+(/\S*)?$#", 7, 255))
			continue;
		
		// Check if the feed exists
		$select = $mysql->prepare("SELECT feed_id FROM feeds WHERE feed_url=:feed LIMIT 1");
		$select->bindParam(":feed", $url);
		
		if(!$select->execute())
			continue;
		
		$result = $select->fetch();
		if(!$result) {
			// Try to add the new feed
			$insert = $mysql->prepare("INSERT INTO feeds(feed_id, feed_url, feed_title, feed_description, feed_date) VALUES(NULL, :feed, 'Loading soon', 'Your new feed will load soon', 0)");
			$insert->bindParam(":feed", $url);
			
			if(!$insert->execute())
				continue;
			
			// Get the feed ID
			$select = $mysql->prepare("SELECT feed_id FROM feeds WHERE feed_url=:feed LIMIT 1");
			$select->bindParam(":feed", $url);
			
			if(!$select->execute())
				continue;
			
			$result = $select->fetch();
			if(!$result)
				continue;
		}
		
		$feed = $result["feed_id"];
		
		// Try to add the feed to the user
		$insert = $mysql->prepare("INSERT INTO user_feeds(user_ref, feed_ref) VALUES(:user, :feed)");
		$insert->bindParam(":user", $user);
		$insert->bindParam(":feed", $feed);
		
		if(!$insert->execute())
			continue;
		
		$success++;
	}
	
	$result = floor(100 * $success / $feeds->length);
	$json_result["percentage"] = $result;
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
