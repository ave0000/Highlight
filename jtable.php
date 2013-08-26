<?php

// The queues we'd like to see with a friendly name
$profiles = array(
    "Enterprise All"=>"EntAll",
    "Enterprise All (Linux)"=>"EntAll",
    "Enterprise All (Windows)"=>"EntAll",
    "Enterprise East (Linux)"=>"EastLin",
    "Enterprise East (Windows)"=>"EastWin",
    "Enterprise West (Linux)"=>"WestLin",
    "Enterprise West (Windows)"=>"WestWin",
    "Enterprise F1000 (Linux)"=>"F1kLin",
    "Enterprise F1000 (Windows)"=>"F1kWin",
    "Enterprise Cloud Only"=>"Cloud",
    "Enterprise ARIC Only"=>"Aric"
);

function saveCache($data,$name='cache') {
    $filename = 'cache/'.$name.'.json';
    file_put_contents($filename,$data);
}

function getProfileData($profile) {
    $hasCache = getCachedProfile($profile);
    if($hasCache !== false)
        return json_decode($hasCache);
    $slick = 'http://oneview.rackspace.com/slick.php';
    $url = $slick . "?fmt=json&latency=latency_20&profile=" . urlencode($profile);
    $contents = file_get_contents($url);
    saveCache($contents,$profile);
    return json_decode($contents);
}
function getCachedProfile($profile,$age=60){
    $cacheFile = 'cache/'.$profile.'.json';
    if(!file_exists($cacheFile))
        return false;

    $mtime = filemtime($cacheFile);
    $fileage = time() - $mtime;
    #echo "file age".$fileage."<br>\n";
    if($fileage > 60 ) {
        return false;
    }
    return file_get_contents($cacheFile);
}

function getProfileList(){
    //ick ...
    include('profile_list.inc');
    $out = array();
    $profiles = array_keys($qs);

    foreach($profiles as $q) {
        if(strpos($q, "Corp") === FALSE && strpos($q,"Strat") === FALSE)
            $out[$q] = $q;
    }
    return $out;
}

//Get data
function getQueueData($profiles) {
    $data = array();
    foreach($profiles as $profile => $name) {
        $data[$profile] = getProfileData($profile);
    }
    return $data;
}

function getSummaries($profiles,$date='') {
    $data = getQueueData($profiles);
    $summary->timeStamp = time();
    foreach($data as $profile => $object) {
        $summary->summaries[] = $object->summary;
    }
    return $summary;
}

#echo json_encode($summary);
/* END: data collector */

//require_once('printQueue.php');

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




$filters = array(
    "Feedback Received"=>findStatus,
    "Multi-Account"=>findMultiTicketAccounts,
    "Fine Wines (Aged)"=>findAgedTickets
);

/*
$filters = array( 
    "Feedback Received" => array( 
        "fn" => findStatus,
        "parameters" => array(
            "Status" => "Feedback Received"
        ),
    ),
    "Multi-Account" => array(
        "fn" => findMultiTicketAccounts,
        "parameters" => array(
            "min_tickets" => 4
        ),
    ),
    "Fine Wines (Aged)"=> array(
        "fn" => findAgedTickets,
        "parameters" => array(
            "hours"=> 4
        )
    )
);
*/

if(isset($_REQUEST['showProfiles'])) {
    echo json_encode(getProfileList());
    #echo json_encode($profiles);
}

if(isset($_REQUEST['showFilters'])){
    echo json_encode(array_keys($filters));
}

#@include_once('outToday.php');
if($_REQUEST['summary'] == 'get'){
    #$data = getQueueData($profiles);
    $summary = getSummaries($profiles);
    echo json_encode($summary);
}

if($_REQUEST['table'] == "feedback") {
    $workingQueue = getProfileData($profiles[0])->queue;
    //echo json_encode($workingQueue);
    echo json_encode(findStatus($workingQueue));
}


if(isset($_REQUEST['queue'])) {
    //create an 'identifyProfile' fn
    $reqQ = $_REQUEST['queue'];
    $profiles = getProfileList();
    $selectedProfile = $profiles['Enterprise All'];
    if(in_array($reqQ,$profiles)) {
        $selectedProfile = array_search($reqQ,$profiles);
    }else if(in_array($reqQ,$profiles)){
        $selectedProfile = $profiles[$reqQ];
    }
    $queueData = getProfileData($selectedProfile)->queue;

    if(isset($_REQUEST['filter']) && in_array($_REQUEST['filter'],array_keys($filters))) {
        //do filter
        $filter = $filters[$_REQUEST['filter']];
        $queueData = $filter($queueData);
    }
    echo json_encode($queueData);
}

?>
