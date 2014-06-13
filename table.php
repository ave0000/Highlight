<html>
<head>
<link rel="stylesheet" type="text/css" href="css/queueview.css">
</head>

<body>
<h1>HighLight</h1>
<?php
date_default_timezone_set('America/Chicago');
$pageStartTime = microtime(true);

/* data collector */

// The queues we'd like to see
$profiles = array(
    "Enterprise All"=>"EntAll",
    "Enterprise All (Linux)"=>"EntLin",
    "Enterprise All (Windows)"=>"EntWin",
    "Enterprise Cloud Only"=>"Cloud",
);

//Get data

$fetchStart = microtime(true);
require_once('summary.php');
foreach($profiles as $profile => $name) {
    $urlprofile = urlencode($profile);
    $url = "http://localhost/jtable.php?queue=" . $urlprofile;
    $data[$profile] = json_decode(file_get_contents($url));

    $sum = $summary->summary[$profile] = getSummary($profile);
}
$fetchElapsed = microtime(true) - $fetchStart;

$summary->timeStamp = time();
/* END: data collector */

//helper functions to print the tables
require_once('printQueue.php');
require_once('filters.php');

//start by printing out the staffing calendar
@include_once('outToday.php');

$workingQueue = $data['Enterprise All'];

echo "<h1>Depth and Breadth</h1>";
echo summaryTable($summary);

echo "<h1>Fine Wines</h1>";
echo printQueue(findAgedTickets($workingQueue));

echo "<h1>Feedback</h1>";
echo printQueue(findStatus($workingQueue));

#echo "<h1>MultiBall</h1>";
#echo printQueue(findMultiTicketAccounts($workingQueue,3));

$pageTimeElapsed = microtime(true) - $pageStartTime;
$pageTimeActual = $pageTimeElapsed - $fetchElapsed;
echo "<div class=debug>".$pageTimeElapsed." - ".$fetchElapsed." = ".$pageTimeActual."secs </div>";

?>
</body></html>
