<?php

/**
 * this is my example of a simple html email template, with a copy attached
 * the template is example.html
 * 
 * @note 
 * Please do not assume that this works the way you think it is.
 * I have NOT tested this.
 * 
 * @author Joseph Dotson (THTime)
 */

require("Mailer.php");

$cclist = array();
$cclist[] = "cc1@example.com";
$cclist[] = "cc2@example.com";
$cclist[] = "cc3@example.com";
$cclist[] = "cc4@example.com";

$mailer = Mailer::GetMailer();
$mailer->AddTo("john@example.com");

foreach ($cclist as $ccemail) {
	$mailer->AddCc($ccemail);
}

$mailer->LoadBodyFromFile("example.html"); // this loads the body to $this->Body
$mailer->ReplaceBodyKey("last_name", "Harker");
$mailer->AddRawAttachment($mailer->Body, "copy.html"); // attach a copy of the email for record keeping
$mailer->IsHTML = true;
$mailer->Subject = "Thank You!";
$mailer->From = "no-reply@example.com";
$mailer->Send();