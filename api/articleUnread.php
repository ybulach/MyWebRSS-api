<?php
require_once("lib.php");

try {
	// Check the arguments
	$result = check_token();
	
	if(!isset($_GET["article"]) || !check_arg($_GET["article"], "#^[0-9]+$#", 1, 10))
		throw new Exception("article");
	$article = $_GET["article"];
	
	// Mark the article as read
	$delete = $mysql->prepare("DELETE FROM user_articles WHERE user_ref=:user AND article_ref=:article");
	$delete->bindParam(":user", $user);
	$delete->bindParam(":article", $article);
	$delete->execute();
	
	if(!$delete)
		send_error("Could not unread the article");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
