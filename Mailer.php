<?php

/**
 * this is the base class for mailers
 * 
 * @version 1.1.0
 * @author Joseph Dotson (THTime)
 * 
 * @copyright
 * Copyright 2012 Joseph Dotson
 * 
 * DOTMailer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	public $NoAlt = true;

	/**
	 * this function will generate and send the email.
	 * it is NON destructive. so if it fails you can try again after you fix it
	 */
	public abstract function Send();

	/**
	 * This function adds a To to the mail, at least one of these is required
	 * @param String $email the email to send the mail to
	 * @param String $name the name of the individual
	 */
	public abstract function AddTo($email, $name = "");

	/**
	 * this function adds a Cc to the mail, this is optional
	 * @param String $email the email to send the mail to
	 * @param String $name the name of the individual
	 */
	public abstract function AddCc($email, $name = "");

	/**
	 * this function adds a Bcc to the mail, this is optional
	 * @param String $email the email to send the mail to
	 * @param String $name the name of the individual
	 */
	public abstract function AddBcc($email, $name = "");

	/**
	 * this function adds an attachment to the email.
	 * 
	 * You should not have to change the $type, unless you are working with an application
	 * that depends on it
	 * 
	 * @param String $path the path to the file
	 * @param String $name the name of the attachment
	 * @param String $type the MIME type of the attachment.
	 * 
	 * @see Send
	 * 
	 * @note file_get_contents WILL parse php before you get it
	 */
	public abstract function AddAttachment($path, $name, $type = "application/octet-stream");

	/**
	 * this function adds an attachment of plain text or data. it WILL handle the base64_encode for you.
	 * 
	 * You should not have to change the $type, unless you are working with an application
	 * that depends on it
	 * 
	 * @param Mixed $data the data stream you want to attach
	 * @param String $name the name of the attachment
	 * @param String $type the MIME type of the attachment.
	 * 
	 * @see Send
	 * 
	 * @note file_get_contents WILL parse php before you get it
	 */
	public abstract function AddRawAttachment($data, $name, $type = "application/octet-stream");

	/* this is for easy copying
	public abstract function Send();
	public abstract function AddTo($email, $name = "");
	public abstract function AddCc($email, $name = "");
	public abstract function AddBcc($email, $name = "");
	public abstract function AddAttachment($path, $name, $type = "application/octet-stream");
	public abstract function AddRawAttachment($data, $name, $type = "application/octet-stream");
	*/

	/**
	 * simple function to load a body from a file
	 * 
	 * @param String $path the path of the file to load
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
	 * 
	 * @param String $key the key to search for
	 * @param String $value the values to replace the key with
	 */
	public function ReplaceBodyKey($key, $value)
	{
		if($this->Body == null)
			throw new Exception("Please load the body!", 1);
		
		$this->Body = str_replace("[[{$key}]]", $value, $this->Body);
	}

	/**
	 * This is the factory method to get a mailer.
	 * 
	 * @param String $mailer the name of the driver
	 * 
	 * @return Mixed the mailer object
	 */
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