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
	
	// Create the hashed + salted password
	$password = sha1($_GET["password"]."mywebrss");
	
	// Try to create the new account
	$insert = $mysql->prepare("INSERT INTO users(user_id, user_email, user_pass) VALUES(NULL, :email, :password)");
	$insert->bindParam(":email", $_GET["email"]);
	$insert->bindParam(":password", $password);
	
	if(!$insert->execute())
		throw new Exception("Can not create a new account. Maybe this email address is already registered");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
