<?php

$date = time();

$to = "avery.scott@Rackspace.com";
$subject = "Test email, please ignore".$date;


#$headers = "From: root@hbops.localdomain\n";
#$headers .= "Reply-To: avery.scott@Rackspace.com\n";
#$headers .= "MIME-Version: 1.0\n";
#$headers .= "Content-Type: text/html; charset=ISO-8859-1\n";

$boundary = uniqid('np');
// To send HTML mail, the Content-type header must be set
$headers .= "To: ".$to."\r\n";
$headers .= "From: Avery Scott <avery.scott@rackspace.com>\r\n";
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= "Content-Type: multipart/alternative;boundary=" . $boundary . "\r\n";
#$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";



//Plain text body
$message .= "Hello,\nThis is a text email, the text/plain version.\n\nRegards,\nTester";
$message .= "\r\n\r\n--" . $boundary . "\r\n";
$message .= "Content-type: text/html; charset=iso-8859-1\r\n\r\n";

$table = "This is a test table! <table border=8>"
        . "<tr>"
        . "<th width=65>Time</th>"
        . "<th>Queue Latency</th>"
        . "<th>Enterprise Total</th>"
        . "<th>Score(Internal)</th>"
        . "</tr>"
        . "<tr>"
        . "<th align=center>".$time."</th>"
        . "<th ".$color3."align=center>".$latency_min.' Minutes' . "</th>"
        . "<th ".$color2."align=center>".$count."</th>"
        . "<th ".$color."align=center>".$score."</th>"
        . "</tr>"
        . "</table>";


    $body = "<html><body>".$table
        . "<br><h4>Total New Tickets Today: ".$tickets."</h4>"
        . "<h4>Alert Percentage: ".$alertpercent."</h4></body></html>";
#$body = "This is a test email I am testing email.";
$body = "<h1>lololhi2u</h1>";


if (mail('', $subject, $message.$body, $headers)) {
   echo("<p>Message passed to mailer</p>");
  } else {
   echo("<p>Message delivery failed...</p>");
  }
?>
