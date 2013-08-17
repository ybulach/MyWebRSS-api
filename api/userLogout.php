<?php
require_once("lib.php");

try {
	// Check the arguments
	check_token();
	
	$token = $_GET["token"];
	
	$delete = $mysql->prepare("DELETE FROM tokens WHERE token_id=:token");
	$delete->bindParam(":token", $token);
	
	if(!$delete->execute())
		throw new Exception("Can not delete the Token. Try again later");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
