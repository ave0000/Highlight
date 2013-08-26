<?php

$date = time();

$to = "avery.scott@Rackspace.com";
$subject = "Test email, please ignore".$date;


//add From: header 
$headers = "From: Avery Scott <avery.scott@rackspace.com>\r\n";
$headers .= "To: ".$to."\r\n";

//specify MIME version 1.0 
$headers .= "MIME-Version: 1.0\r\n"; 

//unique boundary 
$boundary = uniqid("HTMLDEMO"); 

//tell e-mail client this e-mail contains//alternate versions 
$headers .= "Content-Type: multipart/mixed; boundary = $boundary\r\n\r\n"; 

//plain text version of message 
$body = "--$boundary\r\n" . 
   "Content-Type: text/plain; charset=ISO-8859-1\r\n" . 
   "Content-Transfer-Encoding: base64\r\n\r\n"; 
$body .= chunk_split(base64_encode("This is the plain text version!")); 

//HTML version of message 
$body .= "--$boundary\r\n" . 
   "Content-Type: text/html; charset=ISO-8859-1\r\n" . 
   "Content-Transfer-Encoding: base64\r\n\r\n"; 
$body .= chunk_split(base64_encode("This the <b>HTML</b> version!")); 

//send message 

if (mail('', $subject, $body, $headers)) {
   echo("<p>Message passed to mailer</p>");
  } else {
   echo("<p>Message delivery failed...</p>");
  }
?>
