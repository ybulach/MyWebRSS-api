<?php
require_once("lib.php");

try {
	// Check the arguments
	$user = check_token($_GET["token"]);
	
	if(!isset($_GET["old_password"]) || !check_arg($_GET["old_password"], "", 3, 255))
		throw new Exception("old_password");
	
	if(!isset($_GET["password"]) || !check_arg($_GET["password"], "", 3, 255))
		throw new Exception("password");
	
	if(!isset($_GET["confirm_password"]) || !check_arg($_GET["confirm_password"], "", 3, 255))
		throw new Exception("password");
	
	if(strcmp($_GET["password"], $_GET["confirm_password"]) != 0)
		throw new Exception("confirm_password");
	
	// Verify the password
	$select = $mysql->prepare("SELECT user_id FROM users WHERE user_id=:user AND user_pass=:pass");
	$select->bindParam(":user", $user);
	$select->bindParam(":pass", $_GET["old_password"]);
	
	if(!$select->execute())
		throw new Exception("Can not verify the old password. Try again later");
	
	if($select->fetch())
		throw new Exception("old_password");
	
	// Create the hashed + salted password
	$password = sha1($_GET["password"].$password_salt);
	
	// Try to change the password
	$update = $mysql->prepare("UPDATE users SET user_pass=:pass WHERE user_id=:user");
	$update->bindParam(":pass", $password);
	$update->bindParam(":user", $user);
	
	if(!$update->execute())
		throw new Exception("Can not change the password. Try again later");
}
catch( Exception $e ){
	send_error($e->getMessage());
}

// Send the result
send_result($json_result);
?>
