<?php
require_once("lib.php");

try {
	$result = check_token();
	
	// Add the account  if there is no result
	if(!$result->id) {
		if(!$result->email)
			throw new Exception("Can not get account informations. Try again later");
		
		$time = time();
		$insert = $mysql->prepare("INSERT INTO users(user_id, user_email, user_lastlogin) VALUES(NULL, :email, :time)");
		$insert->bindParam(":email", $result->email);
		$insert->bindParam(":time", $time);
		
		if(!$insert->execute())
			throw new Exception("Can not create a new account. Try again later");
	}
	
	$json_result["email"] = $result->email;
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
