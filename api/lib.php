<?php
require_once("conf.php");
require_once("lib.persona.php");

///////////////////////////////////////////////////////////////////////////////
// Check an argument
function check_arg($arg, $regex, $min, $max) {
	if(!empty($arg))
		if((strlen($arg) >= $min) || ($min == 0))
			if((strlen($arg) <= $max) || ($max == 0))
				if(($regex == "") || preg_match($regex, $arg))
					return true;
	
	return false;
}

///////////////////////////////////////////////////////////////////////////////
// Check a Token
function check_token() {
	global $mysql;
	
	if(!isset($_GET["token"]) || !check_arg($_GET["token"], "#^[a-z0-9]+$#", 40, 40))
		throw new Exception("token");
	
	$token = $_GET["token"];
	
	// Try to find the Token
	$select = $mysql->prepare("SELECT user_ref FROM tokens WHERE token_id=:token LIMIT 1");
	$select->bindParam(":token", $token);
	$success = $select->execute();
	if(!$success)
		throw new Exception("Could not get the account informations. Try again later");
	$result = $select->fetch();
	if(!$result)
		throw new Exception("token");
	
	$id = $result["user_ref"];
	
	// Change the date of the Token
	$time = time();
	$update = $mysql->prepare("UPDATE tokens SET token_date=:time WHERE token_id=:token AND user_ref=:id");
	$update->bindParam(":time", $time);
	$update->bindParam(":token", $token);
	$update->bindParam(":id", $id);
	$update->execute();
	
	// Change the date of the last login
	$update = $mysql->prepare("UPDATE users SET user_lastlogin=:time WHERE user_id=:id");
	$update->bindParam(":time", $time);
	$update->bindParam(":id", $id);
	$update->execute();
	
	return $id;
}

///////////////////////////////////////////////////////////////////////////////
// Check a Persona assertion
function check_persona() {
	global $PERSONA_AUDIENCE;
	
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
	
	return $result->email;
}

///////////////////////////////////////////////////////////////////////////////
// Send an error result
function send_error($error) {
	$error_result["success"] = 0;
	$error_result["error"] = $error;
	
	send_result($error_result);
	die();
}

// Send a result
function send_result($result) {
	header_remove();
	//header("Content-Type: application/json");
	echo json_encode($result);
}

///////////////////////////////////////////////////////////////////////////////
// Do not display errors
//error_reporting(0);

// The JSON result
$json_result["success"] = 1;

// Connect to MySQL
try {
	$mysql = new PDO("mysql:host=".$mysql_host.";dbname=".$mysql_base, $mysql_user, $mysql_pass);
	$mysql->exec("SET CHARACTER SET utf8");
}
catch(Exception $e) {
	send_error($e->getMessage());
}

?>
