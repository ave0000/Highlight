<html>
<head>
    <style type="text/css">
        body    {font:8pt helvetica;}
        tr:nth-child(odd)       { background-color:#eee; }
        tr:nth-child(even)      { background-color:#fff; }
    </style>
</head>
<body>
<?php
//Time and Date setup
$time =  date('H:i');
$date = date("m.d.y");

//Get data
$data = json_decode(file_get_contents('http://oneview.rackspace.com/slick.php?profile=Enterprise%20All&fmt=json&latency=latency_20'));
$data2 = json_decode(file_get_contents('http://oneview.rackspace.com/slick.php?profile=Enterprise%20All%20%28Linux%29&fmt=json&latency=latency_15'));


$queue = $data->queue;

$fields = array(
    "ticket_link",
    "age",
    "score",
    "account_link",
    "subject",
    "team",
    "status_",
    "platforms_"
);

echo "<table>\n\t<tr>\n";
foreach($fields as $field) {
    echo "\t\t<th>";
    echo $field;
    echo "</th>\n";
}
echo "\t</tr>\n";
foreach($queue as $ticket) {
    echo "\t<tr>\n";
    $cells = array();

    foreach($fields as $field) {
        $cells[] = $ticket->{$field};
    }

    foreach ($cells as $cell){
       echo "\t\t<td>";
       echo $cell;
       echo "</td>\n";
    }
    #var_dump($ticket);
    #echo $ticket->subject;
    echo "\t</tr>\n";
}
echo "</table>";


echo "<pre>";
#$summary = $data->$summary;
#var_dump($data->summary);
var_dump($queue[10]);
echo "</pre>";


if (false) {

//Transfer into arrays
$array = (array)$data;
$summary = $array['summary'];
$sumArray = (array)$summary;


$array2 = (array)$data2;
$summary2 = $array2['summary'];
$sumArray2 = (array)$summary2;



//some counts and math
$count = $sumArray['total_count'];
$latency_sec = $sumArray['latency_20'];
$latency_min = $latency_sec / 60;

$latency_sec2 = $sumArray2['latency_15'];
$latency_minL = $latency_sec2 / 60;

//explicit data type set
settype($latency_min, "integer");
settype($latency_minL, "integer");


$score = $latency_min * 2 + $count;
settype($score, "integer");

//conditional programming for color on table

$green = "style='background:#00ff00';";
$yellow = "style='background:#ffff00';";
$red = "style='background:#ff0000';";


if ($score <= 540):
        $color = $green;
elseif ($score > 540 && $score < 1000):
        $color = $yellow;
else:
        $color = $red;
endif;


if ($count <= 60):
        $color2 = $green;
elseif ($count > 60 && $count < 101):
        $color2 = $yellow;
else:
        $color2 = $red;
endif;


if ($latency_min <= 180):
        $color3 = $green;
elseif ($latency_min > 180 && $latency_min < 361):
        $color3 = $yellow;
else:
        $color3 = $red;
endif;

/*
//connect to DB
$con=mysqli_connect('10.7.89.54', 'josuelopez', '65nfDeNb5cxXds89Rt', 'queues');
// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
*/

        mysql_connect('10.7.89.54','josuelopez', '65nfDeNb5cxXds89Rt') or die("Unable to connect to database: " . mysql_error());
        mysql_select_db('queues');

        $today = date("Y-m-d");
        $starttime = $today . " 08:00:00";
        $endtime = $today . " 17:00:00";


        // Total Tickets
        $query = "select count(*) from qstats where first > '$starttime' and first < '$endtime' and view='es_ua'";
        $result = mysql_query($query) or die(mysql_error());
        $tickets = array_pop(mysql_fetch_row($result));

        // Total Alerts
        $query = "select count(*) from qstats where first > '$starttime' and first < '$endtime' and view='es_ua' and subject like '%ALERT%'";
        $result = mysql_query($query) or die(mysql_error());
        $alerts = array_pop(mysql_fetch_row($result));

        // Total Rackwatch
        $query = "select count(*) from qstats where first > '$starttime' and first < '$endtime' and view='es_ua' and subject like '%RACKWATCH%'";
        $result = mysql_query($query) or die(mysql_error());
        $rw = array_pop(mysql_fetch_row($result));

        // Total Sitescope
        $query = "select count(*) from qstats where first > '$starttime' and first < '$endtime' and view='es_ua' and subject like '%SITESCOPE%'";
        $result = mysql_query($query) or die(mysql_error());
        $ss = array_pop(mysql_fetch_row($result));

        // Total Scom
        $query = "select count(*) from qstats where first > '$starttime' and first < '$endtime' and view='es_ua' and subject like '%OPSMGR%'";
        $result = mysql_query($query) or die(mysql_error());
        $scom = array_pop(mysql_fetch_row($result));

        // Total Nimbus
        $query = "select count(*) from qstats where first > '$starttime' and first < '$endtime' and view='es_ua' and subject like '%NIMBUS%'";
        $result = mysql_query($query) or die(mysql_error());
        $nimbus = array_pop(mysql_fetch_row($result));
        mysql_close();

        // MATH TIME!!
        $alertpercent = sprintf("%.0f%%",($alerts/$tickets)*100);

        $rwpercent = sprintf("%.0f%%",($rw/$alerts)*100);
        $sspercent = sprintf("%.0f%%",($ss/$alerts)*100);
        $scompercent = sprintf("%.0f%%",($scom/$alerts)*100);
        $nimpercent = sprintf("%.0f%%",($nimbus/$alerts)*100);

$report =
"======================================\n
WORK BREAKDOWN REPORT FOR $today\n
======================================\n
\n
          == First Shift ==\n
\n
          New Tickets: $tickets\n
          Alerts: $alerts ($alertpercent)\n
               RW: $rwpercent\n
               SS: $sspercent\n
               OPS: $scompercent\n
               NIM: $nimpercent\n
\n
======================================\n";


//email section

$to = "avery.scott@Rackspace.com";
$subject = "Operations Report ".$date;


$headers = "From: Josue.Lopez@Rackspace.com\n";
$headers .= "Reply-To: Josue.Lopez@Rackspace.com\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\n";

$table = "<table border=8>"
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



if (mail($to, $subject, $body, $headers)) {
   echo("<p>Message successfully sent!</p>");
  } else {
   echo("<p>Message delivery failed...</p>");
  }
}
?>
</body></html>
