<?php


//store data for later retrieval
//coupled with getCachedProfile
/*
function saveCache($data,$name='cache') {
    $filename = 'cache/'.$name.'.json';
    @file_put_contents($filename,$data);
}
*/
function saveCache($data,$name='cache'){
	$redis = new Redis();
	$redis->pconnect('127.0.0.1', 6379);
	//$data = json_decode($data);
	$data = serialize($data);
	$redis->set($name, $data);
	$redis->expire($name, 30);
}

/*
function getCachedProfile($profile,$age=60){
    $cacheFile = 'cache/'.$profile.'.json';
    if(!file_exists('cache/'))
        return false;

    if(!file_exists($cacheFile))
        return false;

    $mtime = filemtime($cacheFile);
    $fileage = time() - $mtime;
    if($fileage > 60 ) {
        return false;
    }
    $data = file_get_contents($cacheFile);
    
    return json_decode($data);
}
*/

function getCachedProfile($profile) {
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
	$boop = $redis->get($profile);
	return unserialize($boop);
}

function getProfileData($profile) {
    $hasCache = getCachedProfile($profile);
    if($hasCache !== false) {
        return $hasCache;
    }

    $slick = 'http://oneview.rackspace.com/slick.php';
    $url = $slick . "?fmt=json&latency=latency_1&profile=" . urlencode($profile);
    $contents = file_get_contents($url);
    $data = json_decode($contents);
    saveCache($data,$profile);
    return $data;
}

function getProfileList(){

    $hasCache = getCachedProfile('profileList');
    if($hasCache !== false) 
        return $hasCache;


    //ick ...
    include('profile_list.inc');
    $out = array();
    $profiles = array_keys($qs);
    $excludes = array('Corp','Strat','Priority');
    $e = count($excludes);

    //Rewrite me with a better method of filtering
    foreach($profiles as $q) {
    	$hasBad = false;
    	for($i=0;$i<$e;$i++){
        	if(strpos($q, $excludes[$i])!==FALSE){
    			$hasBad = true;
    			break;
    		}
    	}
    	if(!$hasBad){
    		$out[$q] = $q;
    	}
    } 
    saveCache($out,'profileList');
    return $out;
}

function getProfileListShort() {
    $trims = array(
        'Enterprise '=>'',
        //'Team'=>'T:',
        'Linux'=>'L',
        'Windows'=>'W',
    	'Implementation'=>'imp',
    	'Latin America'=>'LATAM'
    );
    $profiles = getProfileList();

    foreach($profiles as $q => $name){
            $name = str_replace(array_keys($trims),$trims,$name);
            $profiles[$q] = $name;
    }
    return $profiles;
}

//Get data
function getQueueData($profiles) {
    $data = array();
    foreach($profiles as $profile => $name) {
        $data[$name] = getProfileData($profile);
    }
    return $data;
}

function getSummaries($profiles,$date='') {
    $data = getQueueData($profiles);
    $summary->timeStamp = time();
    foreach($data as $profile => $object) {
        $summary->summaries[$profile] = $object->summary;
    }
    return $summary;
}


/* Filters and processing */
function processTest($q,$profile) {
    require_once('score/score.php');
    //queue as ticket
    foreach ($q as $t){
        $t->score = getScore($t,$profile);
	//problems with unicode strings?
        $t->subject = substr($t->subject, 0, 100);
        $t->account_name = substr($t->account_name, 0,40);
        //if($t->oldScore != $t->score)
            $out[] = $t;
    }
    return $out;
}

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

//not sure if there's demand for this on the windows side
function goAway($queue,$type='/^((?!OPSMGR).)*$/'){
	if($type == "") return $queue;
	$out = array();
	foreach($queue as $ticket){
		if(@preg_match($type,$ticket->subject))
			$out[] = $ticket;
	}
	return $out;
}

function accountFind($queue,$value) {
	if($value == "") return $queue;

    foreach($queue as $ticket){
        if(stristr($ticket->account_link,$value)!==FALSE)
        $out[] = $ticket;
    }
	if(count($out) < 1) {
		$foo->subject = "No results for $value";
		$out[] = $foo;
	}
        return $out;
}

//describe the available filters, and their parameters
$fil = '[
        {
                "name":"Feedback Received",
                "fn":"findStatus",
                "parameters": [{"name":"Status","value":"Feedback Received"}]
        },{
                "name":"Multi-Account",
                "fn":"findMultiTicketAccounts",
                "parameters":[{"name":"Min tickets","value":4}]
        },{
                "name":"Fine Wines (Aged)",
                "fn":"findAgedTickets",
                "parameters":[{"name":"hours","value":4}]
        },{
                "name":"Go Away",
                "fn":"goAway",
                "parameters":[{"name":"subject","value":"/^((?!OPSMGR).)*$/"}]
        },{
                "name":"Accounting",
                "fn":"accountFind",
                "parameters":[{"name":"account","value":"AON"}]
	}
]';
$filters = json_decode($fil);

if(isset($_REQUEST['showProfiles'])) {
    echo json_encode(getProfileListShort());
}

if(isset($_REQUEST['showFilters'])){
    echo json_encode($filters);
}

if(isset($_REQUEST['queue'])) {
    //create an 'identifyProfile' fn
    $reqQ = $_REQUEST['queue'];
    $profiles = getProfileListShort();
    if(in_array($reqQ,$profiles)) {
        $selectedProfile = array_search($reqQ,$profiles);
    }else if(in_array($reqQ,$profiles)){
        $selectedProfile = $profiles[$reqQ];
    }else{
    	$selectedProfile = $profiles['Enterprise All'];
    }
    $queueData = getProfileData($selectedProfile)->queue;
    $queueData = processTest($queueData,$selectedProfile);

    if(isset($_REQUEST['filter'])) {
        $filName = $_REQUEST['filter'];
        //do filter
    	foreach($filters as $f){
    		if($f->name == $_REQUEST['filter']) {
    			$filter = $f->fn;
    			if(isset($_REQUEST['filterOpt']))
    				$queueData = $filter($queueData,$_REQUEST['filterOpt']);
    			else
    				$queueData = $filter($queueData);
    		}
	   }
    }
    // optional sortby, ignore if it's not a field

    echo json_encode($queueData);
}
?>
