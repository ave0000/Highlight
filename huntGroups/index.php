<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>HuntGroups</title>
    <link rel="stylesheet" type="text/css" href="../css/queueview.css">
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.0.7/angular.min.js"></script>
</head>
<body>
<script>
angular.module('myApp',[]);
<?php
    $url = 'http://myhuntgroups.rackspace.com/vdns.json';
    $data = file_get_contents($url);
    echo " var jsonData = ".$data.";";
 ?>

</script>
<div ng-app="myApp">
<?php

    require_once('printTable.php');
    $table = printTable(json_decode($data));
echo $table;  
?>
</div>

<div><a href="mailto:avery.scott@rackspace.com?Subject=Highlight">Questions, requests?</a></div>

</body>
</html>

