<?php
require_once("lib.php");

try {
	// Check the arguments
	if(!isset($_GET["email"]) || !check_email($_GET["email"]))
		throw new Exception("email");
	
	if(!isset($_GET["password"]) || !check_arg($_GET["password"], "", 3, 255))
		throw new Exception("password");
	
	if(!isset($_GET["confirm_password"]) || !check_arg($_GET["confirm_password"], "", 3, 255))
		throw new Exception("password");
	
	if(strcmp($_GET["password"], $_GET["confirm_password"]) != 0)
		throw new Exception("confirm_password");
	
	// Verify the email address
	$select = $mysql->prepare("SELECT user_id FROM users WHERE user_email=:email");
	$select->bindParam(":email", $_GET["email"]);
	
	if(!$select->execute())
		throw new Exception("Can not verify the email address. Try again later");
	
	if($select->fetch())
		throw new Exception("Email address already registered");
	
	// Create the hashed + salted password
	$password = sha1($_GET["password"].$password_salt);
	
	// Try to create the new account
	$time = time();
	$insert = $mysql->prepare("INSERT INTO users(user_id, user_email, user_pass, user_lastlogin) VALUES(NULL, :email, :password, :time)");
	$insert->bindParam(":email", $_GET["email"]);
	$insert->bindParam(":password", $password);
	$insert->bindParam(":time", $time);
	
	if(!$insert->execute())
		throw new Exception("Can not create a new account. Try again later");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
