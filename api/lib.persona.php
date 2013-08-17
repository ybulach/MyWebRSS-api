<?php
// Get the Persona account infos
// Downloaded from https://github.com/mozilla/browserid-cookbook/tree/master/php

class Persona
{
	/**
	 * Scheme, hostname and port
	 */
	protected $audience;
	
	/**
	 * Constructs a new Persona (optionally specifying the audience)
	 */
	public function __construct($audience = NULL)
	{
		$this->audience = $audience ?: $this->guessAudience();
	}
	
	/**
	 * Verify the validity of the assertion received from the user
	 *
	 * @param string $assertion The assertion as received from the login dialog
	 * @return object The response from the Persona online verifier
	 */
	public function verifyAssertion($assertion)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://verifier.login.persona.org/verify");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, "assertion=".urlencode($assertion)."&audience=".urlencode($this->audience));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		$response = curl_exec($curl);
		
		// Check for errors
		$error = curl_error($curl);
		if($error)
			throw new Exception($error);
		
		curl_close($curl);
		
		return json_decode($response);
	}
	
	/**
	 * Guesses the audience from the web server configuration
	 */
	protected function guessAudience()
	{
		$audience = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
		$audience .= $_SERVER['SERVER_NAME'] . ':'.$_SERVER['SERVER_PORT'];
		return $audience;
	}
}
?>
