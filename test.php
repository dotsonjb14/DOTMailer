<?php

/**
 * this is my example of a simple html email template, with a copy attached
 * the template is example.html
 */
$mailer = new DOTMailer();
$mailer->AddTo("john@example.com");
$mailer->LoadBodyFromFile("example.html"); // this loads the body to $this->Body
$mailer->ReplaceBodyKey("last_name", "Harker");
$mailer->AddRawAttachment($mailer->Body, "copy.html"); // attach a copy of the email for record keeping
$mailer->Send();