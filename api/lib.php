<?php
require_once("conf.php");

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
// Check an email address
function check_email($email) {
	///return check_arg($email, "#^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,4}$#", 6, 255);
	
	// Code from:
	// 		http://www.linuxjournal.com/article/9585
	$isValid = true;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex)
	{
		$isValid = false;
	}
	else
	{
		$domain = substr($email, $atIndex+1);
		$local = substr($email, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		if ($localLen < 1 || $localLen > 64)
		{
			// local part length exceeded
			$isValid = false;
		}
		else if ($domainLen < 1 || $domainLen > 255)
		{
			// domain part length exceeded
			$isValid = false;
		}
		else if ($local[0] == '.' || $local[$localLen-1] == '.')
		{
			// local part starts or ends with '.'
			$isValid = false;
		}
		else if (preg_match('/\\.\\./', $local))
		{
			// local part has two consecutive dots
			$isValid = false;
		}
		else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
		{
			// character not valid in domain part
			$isValid = false;
		}
		else if (preg_match('/\\.\\./', $domain))
		{
			// domain part has two consecutive dots
			$isValid = false;
		}
		else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
			 str_replace("\\\\","",$local)))
		{
			// character not valid in local part unless 
			// local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local)))
			{
				$isValid = false;
			}
		}
		if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
		{
			// domain not found in DNS
			$isValid = false;
		}
	}
	return $isValid;
}

///////////////////////////////////////////////////////////////////////////////
// Check a Token
function check_token($token) {
	global $mysql;
	
	if(!check_arg($token, "#^[a-z0-9]+$#", 40, 40))
		throw new Exception("token");
	
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
	$date = time();
	$update = $mysql->prepare("UPDATE tokens SET token_date=:date WHERE token_id=:token AND user_ref=:id");
	$update->bindParam(":date", $date);
	$update->bindParam(":token", $token);
	$update->bindParam(":id", $id);
	$update->execute();
	
	$update = $mysql->prepare("UPDATE users SET user_lastlogin=:date WHERE user_ref=:id");
	$update->bindParam(":date", $date);
	$update->bindParam(":id", $id);
	$update->execute();
	
	return $id;
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
