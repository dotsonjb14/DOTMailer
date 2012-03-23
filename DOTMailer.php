<?php

/**
 * This is my php mailer class. it is extremely simple.
 * 
 * Please make a note, unless you have a text/plain email with no attachments, it
 * WILL be sent as a multipart. If you do have that situation though, you should probably not
 * be using this. As it is a waste of resources when you can do a simple mail()
 *
 * PEAR mail is a sin, only use it when in dire circumstances
 * 
 * @author: Joseph Dotson (THTime)
 * @version: beta 1.1.0
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

// pear defines, you will need to fill these in yourself
define("PEAR_SMTP", "localhost");
define("PEAR_PORT", 25);
define("PEAR_USE_AUTH", true);
define("PEAR_USER", "");
define("PEAR_PASS", "");

define("USE_PEAR", false);

class DOTMailer
{
	protected $to = null;
	protected $cc = null;
	protected $bcc = null;
	protected $attachments = null;
	protected $mps = null; // the multi part separator
	protected $is_multi_part = false;

	// pubic vars
	public $From = null; //!< The From email
	public $Body = null; //!< The Body of the email
	public $Subject = ""; //!< The Subject of the email (optional)
	public $IsHTML = false; //!< Whether or not the email is HTML
	public $AltText = "This is a MIME encoded message."; //!< The HTML email alt text

	// fixes
	public $NoAlt = false; // for yahoo

	/**
	 * this function will generate and send the email.
	 * it is NON destructive. so if it fails you can try again after you fix it
	 */
	public function Send()
	{
		if($this->to == null)
			throw new Exception("No TO emails set!", 1);

		if($this->Body == null)
			throw new Exception("No Body was set!", 1);
		
		if(USE_PEAR)
		{
			$this->_sendPear();
			return;
		}

		// set is multipart here
		if($this->attachments != null || $this->IsHTML)
			$this->is_multi_part = true;

		$to = $this->generate_to();
		$header = $this->generate_header();
		$body = $this->generate_body();
		if(!mail($to, $this->Subject, $body, $header))
			throw new Exception("Mail failed to send!", 1);
	}

	/**
	 * I purosely didn't add any extra functions for this.
	 * I did use some existing functions though.
	 * I *could* make this more elegant, however I at least want some
	 * decent speed.
	 *
	 * p.s. As long as PEAR doesn't add any useless garbage, this should all work
	 */
	protected function _sendPear()
	{
		require_once("Mail.php");

		$to = $this->generate_to();

		$headers = array();
		if($this->From != null)
			$headers["From"] = $this->From;

		$headers["Subject"] = $this->Subject;

		// get ccs
		if($this->cc != null)
		{
			$cc = "";
			foreach ($this->cc as $value) 
			{
				$name = $value["name"];
				$email = $value["email"];
				$cc .= "$name <{$email}>, ";
			}
			$headers["Cc"] = rtrim($cc, ", ");
		}

		// get ccs
		if($this->bcc != null)
		{
			$bcc = "";
			foreach ($this->bcc as $value) 
			{
				$name = $value["name"];
				$email = $value["email"];
				$bcc .= "$name <{$email}>, ";
			}
			$headers["Bcc"] = rtrim($bcc, ", ");
		}

		$headers["MIME-Version"] = "1.0";
		$headers["Content-Type"] = ltrim($this->get_content_type(), "Content-Type: ");

		$smtp_settings = array();
		$smtp_settings["host"] = PEAR_SMTP;
		$smtp_settings["auth"] = PEAR_USE_AUTH;
		$smtp_settings["username"] = PEAR_USER;
		$smtp_settings["password"] = PEAR_PASS;

		$body = $this->generate_body();

		$smtp = Mail::factory("smtp", $smtp_settings);

		$mail = $smtp->send($to, $headers, $body);
		if(PEAR::isError($mail))
		{
			echo $mail->getMessage();
		}
	}

	/**
	 * this is the function which generates the To part of the email
	 * @see Send
	 */
	protected function generate_to()
	{
		$buff = "";
		foreach ($this->to as $value) 
		{
			$name = $value["name"];
			$email = $value["email"];
			$buff .= "$name <$email>, ";
		}
		$to = rtrim($buff, ", ");
		return $to;
	}

	/**
	 * this is the function which generates the To part of the email
	 * @see Send
	 */
	protected function generate_header()
	{
		$header = "";

		if($this->From != null)
		{
			$header .= "From: {$this->From}".EOL;
		}
		
		// get ccs
		if($this->cc != null)
		{
			$cc = "Cc: ";
			foreach ($this->cc as $value) 
			{
				$name = $value["name"];
				$email = $value["email"];
				$cc .= "$name <{$email}>, ";
			}
			$header .= rtrim($cc, ", ").EOL;
		}

		// get bccs
		if($this->bcc != null)
		{
			$bcc = "Bcc: ";
			foreach ($this->bcc as $value) 
			{
				$name = $value["name"];
				$email = $value["email"];
				$bcc .= "$name <$email>, ";
			}
			$header .= rtrim($bcc, ", ").EOL;
		}

		$header .= "MIME-Version: 1.0".EOL; 

		// add the content type
		$header .= $this->get_content_type();

		return $header;
	}

	/**
	 * this is the function which chooses the content type
	 * @see Send
	 */
	protected function get_content_type()
	{
		$header = "Content-Type: ";
		if(!$this->is_multi_part && !$this->IsHTML)
		{
			$header .= "text/plain; charset='iso-8859-1'";
		}
		else
		{
			if($this->mps == null)
				$this->mps = md5(time());

			$header .= "multipart/mixed; boundary=\"".$this->mps."\"";
		}

		return $header;
	}

	/**
	 * this is the function which generates the Body part of the email
	 * @see Send
	 */
	protected function generate_body()
	{
		if(!$this->is_multi_part && !$this->IsHTML)
			return $this->Body;

		$body = "";
		if($this->IsHTML && !$this->NoAlt)
		{
			$body .= "--" . $this->mps . EOL;
			$body .= "Content-Transfer-Encoding: 7bit".EOL.EOL;
			$body .=$this->AltText.EOL;
		}
		
		$body .= "--" . $this->mps . EOL;
		$main_type = "";
		if($this->IsHTML)
		{
			$main_type .= "Content-Type: text/html; charset='iso-8859-1'";
		}
		else
		{
			$main_type .= "Content-Type: text/plain; charset='iso-8859-1'";	
		}
		$body .= $main_type.EOL;
		$body .= "Content-Transfer-Encoding: 8bit".EOL.EOL;
		$body .= $this->Body.EOL;

		// do the attachments
		if($this->attachments != null)
		{
			foreach ($this->attachments as $value) 
			{
				$data = $value["data"];
				$name = $value["name"];
				$type = $value["type"];

				$body .= "--" . $this->mps . EOL;
				$body .= "Content-Type: {$type}; name=\"".$name."\"".EOL;
				$body .= "Content-Transfer-Encoding: base64".EOL;
				$body .= "Content-Disposition: attachment".EOL.EOL;
				$body .= $data.EOL;
			}
		}
		$body .= "--" . $this->mps . "--";
		return $body;
	}

	/**
	 * This function adds a To to the mail, at least one of these is required
	 * @param String $email the email to send the mail to
	 * @param String $name the name of the individual
	 */
	public function AddTo($email, $name = "")
	{
		if($this->to == null)
			$this->to = array();
		$tmp = array();
		$tmp["email"] = $email;
		$tmp["name"] = $name;
		$this->to[] = $tmp;
	}

	/**
	 * this function adds a Cc to the mail, this is optional
	 * @param String $email the email to send the mail to
	 * @param String $name the name of the individual
	 */
	public function AddCc($email, $name = "")
	{
		if($this->cc == null)
			$this->cc = array();
		$tmp = array();
		$tmp["email"] = $email;
		$tmp["name"] = $name;
		$this->cc[] = $tmp;
	}

	/**
	 * this function adds a Bcc to the mail, this is optional
	 * @param String $email the email to send the mail to
	 * @param String $name the name of the individual
	 */
	public function AddBcc($email, $name = "")
	{
		if($this->bcc == null)
			$this->bcc = array();
		$tmp = array();
		$tmp["email"] = $email;
		$tmp["name"] = $name;
		$this->bcc[] = $tmp;
	}

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
	public function AddAtachment($path, $name, $type = "application/octet-stream")
	{
		if(!file_exists($path))
			throw new Exception("File does not exist!", 1);
		
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["data"] = chunk_split(base64_encode(file_get_contents($path)));
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}

	/**
	 * this function adds an attachment of plain text or data. it WILL handle the base64_encode for you.
	 * 
	 * You should not have to change the $type, unless you are working with an application
	 * that depends on it
	 * 
	 * @param Binary $data the data stream you want to attach
	 * @param String $name the name of the attachment
	 * @param String $type the MIME type of the attachment.
	 * 
	 * @see Send
	 * 
	 * @note file_get_contents WILL parse php before you get it
	 */
	public function AddRawAttachment($data, $name, $type = "application/octet-stream")
	{
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["data"] = chunk_split(base64_encode($data));
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}

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
}
