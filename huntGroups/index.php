<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Queue Highlights</title>
    <script type='text/javascript' src="http://code.angularjs.org/1.0.1/angular-1.0.1.min.js"></script>
    <script src="ctk.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/queueview.css">
</head>
<body>
<script>
<?php
    $url = 'http://myhuntgroups.rackspace.com/vdns.json';
    $data = file_get_contents($url);
    echo " var jsonData = '".$data."';";
 ?>
</script>

<div ng-app="myApp">
<div ng-controller="Dynamic">

<table>
    <tr>
        <th class="ticketColumnId">Ticket</th>
        <th class="ticketColumnAge">Age</th>
        <th class="ticketColumnAccount">Account</th>
        <th>subject</th>
        <th class="ticketColumnTeam">Team</th>
        <th class="ticketColumnStatus">Status</th>
        <th class="ticketColumnPlatforms">Platforms</th>
    </tr>
    <tr ng-repeat="t in tickets">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>

</div>
</div>

<div><a href="mailto:avery.scott@rackspace.com?Subject=Highlight">Questions, requests?</a></div>

</body>
</html>

