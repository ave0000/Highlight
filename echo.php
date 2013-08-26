<?php
$staffingCal = 'https://www.staffingcalendar.com/combined/api_markedout.php';
$num = 237;
$options = '?team='.$num;
echo file_get_contents($staffingCal.$options);
?>
