<?php
function getTeams() {
    return file_get_contents('https://www.staffingcalendar.com/combined/api_teams.php');
}

function isTeam($num) {
    $teams = getTeams();
    $teams = json_decode($teams);
    foreach($teams as $team) {
        if($num == $team->teamID) {
            return true;
        }
    }
    return false;
}

function getTeam($num = 237,$date=''){
    $staffingCal = 'https://www.staffingcalendar.com/combined/api_markedout.php';
    $options = '?team='.$num;
    if($date != '') {
        $options .= '&qDate='.$date;
    }
    return file_get_contents($staffingCal.$options);
}

if(isset($_REQUEST['listTeams'])) {
   echo getTeams();
}else if(isset($_REQUEST['team'])){
    $num = intval($_REQUEST['team']);
    if(is_int($num) && isTeam($num)){
        echo getTeam($num);
    }
}else
    echo getTeam();

?>
