<?php

/**
 * this is my PEAR smtp implementation
 * 
 * in order to use this you need
 * pear/Mail
 * pear/Mail_Mime
 * 
 * @author Joseph Dotson (THTime)
 */

define("PEAR_PATH", "Mail.php");

define("PEAR_SMTP_HOST", "localhost");
define("PEAR_PORT", 25);
define("PEAR_USE_AUTH", true);
define("PEAR_USER", "");
define("PEAR_PASS", "");

class PEARMail extends Mailer
{
	protected $to = null;
	protected $cc = null;
	protected $bcc = null;
	protected $attachments = null;

	public function Send()
	{
		if($this->to == null)
			throw new Exception("No To emails set!", 1);

		if($this->Body == null)
			throw new Exception("No Body was set!", 1);

		// PEAR want's this set, so you might as well set it.
		if($this->From == null)
			throw new Exception("No From was set!", 1);

		$headers = array();

		$smtp_settings = array();
		$smtp_settings["host"] = PEAR_SMTP_HOST;
		$smtp_settings["port"] = PEAR_PORT;
		$smtp_settings["auth"] = PEAR_USE_AUTH;
		$smtp_settings["username"] = PEAR_USER;
		$smtp_settings["password"] = PEAR_PASS;

		throw new Exception("Not yet implemented", 1);
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
	public function AddRawAtachment($data, $name, $type = "application/octet-stream")
	{
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["data"] = $data;
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}
}