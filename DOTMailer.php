<?php

/**
 * This is my php mailer class. it is extremely simple.
 * 
 * Please make a note, unless you have a text/plain email with no attachments, it
 * WILL be sent as a multipart. If you do have that situation though, you should probably not
 * be using this. As it is a waste of resources when you can do a simple mail()
 * 
 * AUTHOR: Joseph Dotson (THTime)
 * VERSION: RC 1.0.1
 * 
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


if(!defined("EOL"))
	define("EOL", PHP_EOL);

class DOTMailer
{
	protected $to = null;
	protected $cc = null;
	protected $bcc = null;
	protected $attachments = null;
	protected $mps = null; // the multi part separator
	protected $is_multi_part = false;

	// pubic vars
	public $From = null;
	public $Body = null;
	public $Subject = "";
	public $IsHTML = false;
	public $AltText = "This is a MIME encoded message."; // this will only be sent in an HTML message

	// the send function...
	public function Send()
	{
		if($this->to == null)
			throw new Exception("No TO emails set!", 1);

		if($this->Body == null)
			throw new Exception("No Body was set!", 1);
		
		// set is multipart here
		if($this->attachments != null || $this->IsHTML)
			$this->is_multi_part = true;

		$to = $this->generate_to();
		$header = $this->generate_header();
		$body = $this->generate_body();
		if(!mail($to, $this->Subject, $body, $header))
			throw new Exception("Mail failed to send!", 1);
	}

	// generation functions
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

	protected function get_content_type()
	{
		$header = "";
		if(!$this->is_multi_part && !$this->IsHTML)
		{
			$header .= "Content-Type: text/plain; charset='iso-8859-1'";
		}
		else
		{
			if($this->mps == null)
				$this->mps = md5(time());

			$header .= "Content-Type: multipart/mixed; boundary=\"".$this->mps."\"";
		}

		return $header;
	}

	protected function generate_body()
	{
		if(!$this->is_multi_part && !$this->IsHTML)
			return $this->Body;

		$body = "";
		if($this->IsHTML)
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

	/*
	This function adds a To to the mail, at least one of these is required
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

	/*
	this function adds a Cc to the mail, this is optional
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

	/*
	this function adds a Bcc to the mail, this is optional
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

	// this function adds an attachment to the email
	// NOTE: file_get_contents WILL parse php before you get it
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

	// this function adds an attachment of plain text or data. it WILL handle the base64_encode for you
	public function AddRawAttachment($data, $name, $type = "application/octet-stream")
	{
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["data"] = chunk_split(base64_encode($data));
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}

	// simple function to load a body from a file
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

/*
test code, this should not run if this file is included in another file
it will only run if this page is run directly
*/
if(count(debug_backtrace()) == 0)
{
    $mailer = new DOTMailer();
	$mailer->LoadBodyFromFile("test.html");
	$mailer->ReplaceBodyKey("my_text", "Is Awesome!");
	$mailer->IsHTML = true;
	$mailer->AltText = "Please use an HTML browser to view this";
	$mailer->AddTo("joseph@enablepoint.com");
	$mailer->AddAtachment("index.html", "index.html");
	$mailer->AddCc("test@test.com");
	$mailer->AddCc("test@test.com");
	echo $mailer->Body;
	$mailer->Send();
}