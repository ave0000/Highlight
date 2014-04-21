<?php

if(isset($_REQUEST['calTeam'])) {
	$calTeam = $_REQUEST['calTeam'];
	//numbers only kthx
	$calTeam = preg_replace('/[^0-9]/', '', $calTeam);
}else{
	$calTeam = '237';
}
/* Staffing Calendar Data */
#$outtoday = json_decode(file_get_contents('https://www.staffingcalendar.com/combined/api_markedout.php?qDate=2013-04-15&team=237'));
$outtoday = json_decode(file_get_contents('https://www.staffingcalendar.com/combined/api_markedout.php?team='.$calTeam));
$coverage = json_decode(file_get_contents('https://www.staffingcalendar.com/combined/api_dailycoverage.php?team='.$calTeam));
$outfields = array(
    "firstName"=>"First",
    "lastName"=>"Last",
    "specialty"=>"Specialty",
    "notes"=>"Reason"
);
echo "<div id=outToday>\n";
echo "<h1>Rackers Out</h1>";
echo "<table>\n\t<tr>\n";
foreach($outfields as $field) {
    echo "\t\t<th>$field</th>\n";
}
echo "\t</tr>\n";
foreach($outtoday as $delinquint) {
       echo "\t<tr>\n";
    foreach(array_keys($outfields) as $field) {
        echo "\t\t<td>".$delinquint->{$field}."</td>\n";
    }
    echo "\t</tr>\n";
}
echo "</table>\n</div>";

$totals = [];
foreach($coverage as $guy) {
	$os = $guy->OS;
	if(isset($totals[$os])) $totals[$os]++;
	else $totals[$os] = 1;
}

echo "<div id=coverageTotal><h1>Coverage Totals</h1>";
echo "<table><tr><th>OS</th><th>Total</th></tr>";
foreach($totals as $os=>$t) {
	echo "<tr><td>".$os."</td><td>".$t."</td></tr>";
}
echo "</table></div>";

/* END: Staffing Calendar Data */
?>
