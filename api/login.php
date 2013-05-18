<?php
require_once("lib.php");

try {
	// Check the arguments
	if(!isset($_GET["email"]) || !check_email($_GET["email"]))
		throw new Exception("email");
	
	if(!isset($_GET["password"]) || !check_arg($_GET["password"], "", 3, 255))
		throw new Exception("password");
	
	// Create the hashed + salted password
	$password = sha1($_GET["password"]."mywebrss");
	
	// Try to get the account id
	$select = $mysql->prepare("SELECT user_id FROM users WHERE user_email=:email AND user_pass=:password LIMIT 1");
	$select->bindParam(":email", $_GET["email"]);
	$select->bindParam(":password", $password);
	$success = $select->execute();
	
	$result = $select->fetch();
	if(!$success || !$result)
		throw new Exception("No account found. Check the email and the password");
	
	$user = $result["user_id"];
	
	// Create a new Token, expiring in 30 days
	$date = time()+60*60*24*30;
	$token = sha1($_GET["email"].$date.$user);
	
	$insert = $mysql->prepare("INSERT INTO tokens(token_id, user_ref, token_date) VALUES(:token, :user, :date)");
	$insert->bindParam(":token", $token);
	$insert->bindParam(":user", $user);
	$insert->bindParam(":date", $date);
	
	if(!$insert->execute())
		throw new Exception("Can not create a new Token. Try again later");
	
	$json_result["token"] = $token;
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
