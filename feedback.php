<html>
<head>

<link rel="stylesheet" type="text/css" href="css/queueview.css">

</head>
<body>
<table>
<?php
//Time and Date setup

//Get data
$data = json_decode(file_get_contents('http://oneview.rackspace.com/slick.php?profile=Enterprise%20All&fmt=json'));

$queue = $data->queue;

$fields = array(
    "ticket_link",
    "age",
    "score",
    "account_link",
    "subject",
    "team",
    "status_",
    "status",
    "platforms_"
);

echo "\t<tr>\n";
foreach($fields as $field) {
    echo "\t\t<th>";
    echo $field;
    echo "</th>\n";
}
echo "\t</tr>\n";

foreach($queue as $ticket) {
    if ($ticket->status != "Feedback Received") {
        continue;
    }

    echo "\t<tr>\n";
    $cells = array();

    foreach($fields as $field) {
       echo "\t\t<td>";
       echo $ticket->{$field};
       echo "</td>\n";
    }
    #var_dump($ticket);
    #echo $ticket->subject;
    echo "\t</tr>\n";
}
/*
echo "<pre>";
#$summary = $data->$summary;
#var_dump($data->summary);
var_dump($queue[10]);
echo "</pre>";
*/
?>
</table></body></html>
