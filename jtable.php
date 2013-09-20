<?php


//store data for later retrieval
//coupled with getCachedProfile
function saveCache($data,$name='cache'){
	$redis = new Redis();
	$redis->pconnect('127.0.0.1', 6379);
	//$data = json_decode($data);
	$data = serialize($data);
	$redis->set($name, $data);
	$redis->expire($name, 30);
}

function getCachedProfile($profile) {
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
	$boop = $redis->get($profile);
	return unserialize($boop);
}

function getProfileData($profile) {
    $hasCache = getCachedProfile($profile);
    if($hasCache !== false)
        return $hasCache;

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
    if(!is_array($q) || count($q)==0) return $q;
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

if(isset($_REQUEST['showProfiles'])) {
    echo json_encode(getProfileListShort());
}

if(isset($_REQUEST['showFilters'])){
    require_once('filters.php');
    echo $filters;
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
        require_once('filters.php');
        $filters = json_decode($filters);
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
