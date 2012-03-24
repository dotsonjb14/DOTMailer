<?php

/**
 * This is my php mailer class. it is extremely simple.
 * 
 * Please make a note, unless you have a text/plain email with no attachments, it
 * WILL be sent as a multipart. If you do have that situation though, you should probably not
 * be using this. As it is a waste of resources when you can do a simple mail()
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

class DOTMailer extends Mailer
{
	protected $to = null;
	protected $cc = null;
	protected $bcc = null;
	protected $attachments = null;
	protected $mps = null; // the multi part separator
	protected $is_multi_part = false;

	public function Send()
	{
		if($this->to == null)
			throw new Exception("No To emails set!", 1);

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

	public function AddTo($email, $name = "")
	{
		if($this->to == null)
			$this->to = array();
		$tmp = array();
		$tmp["email"] = $email;
		$tmp["name"] = $name;
		$this->to[] = $tmp;
	}

	public function AddCc($email, $name = "")
	{
		if($this->cc == null)
			$this->cc = array();
		$tmp = array();
		$tmp["email"] = $email;
		$tmp["name"] = $name;
		$this->cc[] = $tmp;
	}

	public function AddBcc($email, $name = "")
	{
		if($this->bcc == null)
			$this->bcc = array();
		$tmp = array();
		$tmp["email"] = $email;
		$tmp["name"] = $name;
		$this->bcc[] = $tmp;
	}

	public function AddAttachment($path, $name, $type = "application/octet-stream")
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

	public function AddRawAttachment($data, $name, $type = "application/octet-stream")
	{
		if($this->attachments == null)
			$this->attachments = array();

		$tmp["data"] = chunk_split(base64_encode($data));
		$tmp["name"] = $name;
		$tmp["type"] = $type;
		$this->attachments[] = $tmp;
	}
}
