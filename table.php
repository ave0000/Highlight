<html>
<head>
<link rel="stylesheet" type="text/css" href="css/queueview.css">
</head>

<body>
<h1>HighLight</h1>
<?php
$pageStartTime = microtime(true);
date_default_timezone_set('America/Chicago');

@include_once('outToday.php');

/*
 * Given a number of seconds, return a contextual string
 */
function secs_to_str($x) {
        if ($x == 0) return 0;

        $str = "";

        if ($x >= 86400) {
                $n = intval($x / 86400);
                $x -= ($n * 86400);
                $str .= sprintf("%dd", $n);
        }

        if ($x >= 3600) {
                $n = intval($x / 3600);
                $x -= ($n * 3600);
                $str .= sprintf("%dh", $n);
        }

        if ($x > 60) {
                $n = intval($x / 60);
                $x -= ($n * 60);
                $str .= sprintf("%dm", $n);
        }

        if (!strlen($str)) {
                $str = $x."s";
        }

        return $str;
}

/* data collector */

// The queues we'd like to see with a friendly name
$profiles = array(
    "Enterprise All"=>"EntAll",
    "Enterprise East (Linux)"=>"EastLin",
    "Enterprise East (Windows)"=>"EastWin",
    "Enterprise West (Linux)"=>"WestLin",
    "Enterprise West (Windows)"=>"WestWin",
    "Enterprise F1000 (Linux)"=>"F1kLin",
    "Enterprise F1000 (Windows)"=>"F1kWin",
    "Enterprise Cloud Only"=>"Cloud",
    "Enterprise ARIC Only"=>"Aric"
);

//Get data
$slick = 'http://oneview.rackspace.com/slick.php';
$slickStart = microtime(true);
foreach($profiles as $profile => $name) {
    $url = $slick . "?fmt=json&latency=latency_20&profile=" . urlencode($profile);
    $data[$profile] = json_decode(file_get_contents($url));
    $summary->summary[$name] = $data[$profile]->summary;
}
$slickElapsed = microtime(true) - $slickStart;

$summary->timeStamp = time();
#echo json_encode($summary);
/* END: data collector */


//read a json block and turn it into a table
function summaryTable($summary) {
    $table = "<table>\n\t<tr>\n";

    $table .= "\t\t<th>Time</th>\n";
    foreach($summary->summary as $name => $profile) {
        $table .= "\t\t<th>$name</th>\n";
    }
    $table .= "\t</tr>\n";
    $table .= "\t<tr>\n";

    $table .= "\t\t<td rowspan=2>".date('H:i',$summary->timeStamp)."</td>\n";
    foreach($summary->summary as $profile) {
        $table .= "\t\t<td>";
        $table .= $profile->total_count;
        $table .= "</td>\n";
    }

    $table .= "\t</tr>\n";
    $table .= "\t<tr>\n";

    foreach($summary->summary as $profile) {
        $table .= "\t\t<td>";
        $table .= secs_to_str($profile->latency_20);
        $table .= "</td>\n";
    }

    $table .= "\t</tr>\n</table>";
    return $table;
}

require_once('printQueue.php');

//given a queue, find the tickets that are over 'hours' old
function findAgedTickets($queue,$hours=4) {
    $out = array();
    $min_seconds = $hours * 3600;

    foreach($queue as $ticket) {
        if($ticket->age_seconds >= $min_seconds) {
            $out[] = $ticket;
        }
    }
    return $out;
}


//given a queue, return a queue of tickets with a certain 'status'
function findStatus($queue,$status="Feedback Received") {
    $getStatuses = function($t)use($status) {
        return $t->status == $status;
    };
    return array_filter($queue,$getStatuses);
}


//given a queue, return a queue with accounts with at least 'min_count' tickets
function findMultiTicketAccounts($tickets,$min_count=4) {
    //i think this is O(n log n)
    $out = array();

    while($tickets) {
        $test = array(array_pop($tickets));
        $account = $test[0]->account;

        for($i=0;$i<count($tickets);$i++) {
            if($tickets[$i]->account == $account) {
                $test[] = $tickets[$i];
                unset($tickets[$i]);
            }
        }
        if(count($test)>=$min_count)
            $out=array_merge($out,$test);
    }

    return $out;
}


$workingQueue = $data['Enterprise All']->queue;

echo "<h1>Depth and Breadth</h1>";
echo summaryTable($summary);

echo "<h1>Fine Wines</h1>";
echo printQueue(findAgedTickets($workingQueue));

echo "<h1>Feebers</h1>";
echo printQueue(findStatus($workingQueue));

echo "<h1>MultiBall</h1>";
echo printQueue(findMultiTicketAccounts($workingQueue,3));

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

$to = "avery.scott@rackspace.com";
#$to  ="ave0000@gmail.com";
$subject = "DEV: Operations Report ".$date;


$headers = "From: avery.scott@rackspace.com\n";
$headers .= "Reply-To: avery.scott@rackspace.com\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\n";

$table = "<table border=0>"
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

if(false) {
echo "<p>attempting to send mail\n</p>";
if (mail($to, $subject, $body, $headers)) {
   echo("<p>Message successfully sent!</p>");
  } else {
   echo("<p>Message delivery failed...</p>");
  }
}

$pageTimeElapsed = microtime(true) - $pageStartTime;
$pageTimeActual = $pageTimeElapsed - $slickElapsed;
echo "<div class=debug>".$pageTimeElapsed." - ".$slickElapsed." = ".$pageTimeActual."secs </div>";

?>
</body></html>
