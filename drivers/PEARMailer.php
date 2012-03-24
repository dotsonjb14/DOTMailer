<?php

/**
 * this is my PEAR smtp implementation
 * 
 * in order to use this you need
 * pear/Mail
 * pear/Mail_Mime
 * 
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

require_once("Mail.php");
require_once("Mail/mime.php");

define("PEAR_SMTP_HOST", "localhost");
define("PEAR_PORT", 25);
define("PEAR_USE_AUTH", false);
define("PEAR_USER", "");
define("PEAR_PASS", "");

class PEARMailer extends Mailer
{
	protected $to = null;
	protected $cc = null;
	protected $bcc = null;
	protected $attachments = null;

	// this function will send with the default smtp settings
	// feel free to change this. for extra settings
	public function Send()
	{
		$smtp_settings = array();
		$smtp_settings["host"] = PEAR_SMTP_HOST;
		$smtp_settings["port"] = PEAR_PORT;
		$smtp_settings["auth"] = PEAR_USE_AUTH;
		$smtp_settings["username"] = PEAR_USER;
		$smtp_settings["password"] = PEAR_PASS;

		$this->SendWithSettings($smtp_settings);
	}

	/**
	 * This function is used to send custom settings to smtp on a runtime
	 * basis. Send() calls this function after creating the smtp settings based on
	 * the constants
	 * 
	 * @see Send()
	 */
	public function SendWithSettings($smtp_settings)
	{
		if($this->to == null)
			throw new Exception("No To emails set!", 1);

		if($this->Body == null)
			throw new Exception("No Body was set!", 1);

		// PEAR want's this set, so you might as well set it.
		if($this->From == null)
			throw new Exception("No From was set!", 1);

		$headers = array();
		$headers["To"] = $this->to;
		if($this->cc)
			$headers["Cc"] = $this->cc;
		if($this->bcc)
			$headers["Bcc"] = $this->bcc;
		$headers["Subject"] = $this->Subject;
		$headers["From"] = $this->From;

		$mime = new Mail_Mime(array('eol' => EOL));
		if($this->IsHTML)
		{
			if(!$this->NoAlt)
				$mime->setTXTBody($this->AltText);
			$mime->setHTMLBody($this->Body);
		}
		else
		{
			$mime->setTXTBody($this->Body);
		}

		// add attachments
		if($this->attachments != null)
		{
			foreach ($this->attachments as $attachment) 
			{
				if(isset($attachment["data"]))
				{
					// raw attachment
					$mime->AddAttachment($attachment["data"], $attachment["type"], $attachment["name"], false);
				}
				else
				{
					$mime->AddAttachment($attachment["path"], $attachment["type"], $attachment["name"]);
				}
			}
		}

		$body = $mime->get();
		$headers = $mime->headers($headers);
		
		$mail =& Mail::factory("smtp", $smtp_settings);
		$mail->send($this->to, $headers, $body);

		if (PEAR::isError($mail))
			throw new Exception($mail->getMessage(), 1);
	}

	// do DOTMailer like this
	public function AddTo($email, $name = "")
	{
		if($this->to == null)
			$this->to = "";
		else
			$this->to .= ", ";

		$this->to .= "{$name} <{$email}>";
	}

	public function AddCc($email, $name = "")
	{
		if($this->cc == null)
			$this->cc = "";
		else
			$this->cc .= ", ";

		$this->cc .= "{$name} <{$email}>";
	}

	public function AddBcc($email, $name = "")
	{
		if($this->bcc == null)
			$this->bcc = "";
		else
			$this->bcc .= ", ";

		$this->bcc .= "{$name} <{$email}>";
	}

	// this is a slighly modified version from DOTMailer
	public function AddAttachment($path, $name, $type = "application/octet-stream")
	{
		if(!file_exists($path))
			throw new Exception("File does not exist!", 1);
		
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["path"] = $path;
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}

	// do a if(isset($attachment["data"])) to check if it's raw
	public function AddRawAttachment($data, $name, $type = "application/octet-stream")
	{
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["data"] = $data;
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}
}