<?php
require_once("lib.php");

try {
	// Check the arguments
	if(!isset($_GET["assertion"]))
		throw new Exception("assertion");
	
	// Get the email address from Persona
	$persona = new Persona($PERSONA_AUDIENCE);
	$result = $persona->verifyAssertion($_GET["assertion"]);
	if($result->status !== 'okay') {
		if($result->reason == "assertion has expired")
			throw new Exception("assertion");
		else
			throw new Exception($result->reason);
	}
	
	$email = $result->email;
	$json_result["email"] = $email;
	
	// Try to get the account id
	$select = $mysql->prepare("SELECT user_id FROM users WHERE user_email=:email LIMIT 1");
	$select->bindParam(":email", $email);
	if(!$select->execute())
		throw new Exception("Can not get account informations. Try again later");
	
	$result = $select->fetch();
	
	// Add the account if there is no result
	if(!$result) {
		$time = time();
		$insert = $mysql->prepare("INSERT INTO users(user_id, user_email, user_lastlogin) VALUES(NULL, :email, :time)");
		$insert->bindParam(":email", $email);
		$insert->bindParam(":time", $time);
		
		if(!$insert->execute())
			throw new Exception("Can not create a new account. Try again later");
		
		// Get the account id
		$select = $mysql->prepare("SELECT user_id FROM users WHERE user_email=:email LIMIT 1");
		$select->bindParam(":email", $email);
		if(!$select->execute())
			throw new Exception("Can not get account informations. Try again later");
		
		$result = $select->fetch();
		if(!$result)
			throw new Exception("Can not add the account. Try again later");
	}
	
	$user = $result["user_id"];
	
	// Create a new Token
	$date = time();
	$token = sha1($email.$date.$user);
	
	$insert = $mysql->prepare("INSERT INTO tokens(token_id, user_ref, token_date) VALUES(:token, :user, :date)");
	$insert->bindParam(":token", $token);
	$insert->bindParam(":user", $user);
	$insert->bindParam(":date", $date);
	
	if(!$insert->execute())
		throw new Exception("Can not create a new Token. Try again later");
	
	// Update last connection time
	$update = $mysql->prepare("UPDATE users SET user_lastlogin=:date WHERE user_id=:user");
	$update->bindParam(":date", $date);
	$update->bindParam(":user", $user);
	$update->execute();
	
	$json_result["token"] = $token;
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
