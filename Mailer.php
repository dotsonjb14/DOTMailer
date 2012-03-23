<?php

/**
 * this is the base class for mailers
 * 
 * 
 */

define("EOL", PHP_EOL); // I hate wasting 4 characters...

define("DEFAULT_MAILER", "DOTMailer");
define("MAIL_ROOT", dirname(__FILE__)."/");

abstract class Mailer
{
	// pubic vars
	public $From = null; //!< The From email
	public $Body = null; //!< The Body of the email
	public $Subject = ""; //!< The Subject of the email (optional)
	public $IsHTML = false; //!< Whether or not the email is HTML
	public $AltText = "This is a MIME encoded message."; //!< The HTML email alt text

	// you can see the docs in drivers/DOTMailer.php
	// I don't want them here to make adding drivers easier.
	public abstract function Send();
	public abstract function AddTo($email, $name = "");
	public abstract function AddCc($email, $name = "");
	public abstract function AddBcc($email, $name = "");
	public abstract function AddAtachment($path, $name, $type = "application/octet-stream");
	public abstract function AddRawAttachment($data, $name, $type = "application/octet-stream");

	/**
	  * simple function to load a body from a file
	  */ 
	public function LoadBodyFromFile($path)
	{
		if(!file_exists($path))
			throw new Exception("File does not exist!", 1);
		
		$this->Body = file_get_contents($path);
	}

	/**
	 * this function replaces a key in the body with a value
	 * if your key is user_name, is searches for [[user_name]]
	 * in the body and replaces it with the value
	 */
	public function ReplaceBodyKey($key, $value)
	{
		if($this->Body == null)
			throw new Exception("Please load the body!", 1);
		
		$this->Body = str_replace("[[{$key}]]", $value, $this->Body);
	}

	public static function GetMailer($mailer = DEFAULT_MAILER)
	{
		$path = MAIL_ROOT . "drivers/" . $mailer . ".php";
		if(file_exists($path))
		{
			require_once($path);
			if(class_exists($mailer))
			{
				return new $mailer();
			}
			else
			{
				throw new Exception("Mailer $mailer does not exist in $path", 1);
			}
		}
		else
		{
			throw new Exception("File not found in $path", 1);
		}
	}
}