<?php

function saveCache($data,$name='cache'){
	$redis = new Redis();
	$redis->pconnect('127.0.0.1', 6379);
	//$data = json_encode($data);
	$data = serialize($data);
	$redis->set($name, $data);
	$redis->expire($name, 30);
}

function getCachedProfile($profile) {
    $redis = new Redis();
    $redis->pconnect('127.0.0.1',6379);
	$boop = $redis->get($profile);
    if($profile == 'profileList') {
      return unserialize($boop);  
    }else if($profile != 'profileList' && ($boop === false || $boop == false)) {
        $redis->rpush('wantNewQueue',$profile);
        return "try again soon";
    }
    return json_decode($boop);
}

function getProfileData($profile) {
    $list = 'ticketList:'.$profile;
    $redis = new Redis();
    $redis->pconnect('127.0.0.1',6379);
    $boop = $redis->get($list);
    if($boop === false || $boop == false) {
        $redis->rpush('wantNewQueue',$profile);
        return "try again soon";
    }

    $boop = json_decode($boop);
    $out = array();    
    foreach($boop as $t) {
        $element = (object) $redis->hgetall('ticket:'.$t);
        $out[] = $element;
    }
    
    return $out;
}

//a list of profiles, minus the ones we don't want
function getProfileList(){
    if(false !== ($hasCache = getCachedProfile('profileList'))) 
        return $hasCache;

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
    	if(!$hasBad)
    		$out[$q] = $q;
    } 
    saveCache($out,'profileList');
    return $out;
}

//scrunch the profile list names into an associative array
function getProfileListShort() {
    $trims = array(
        'Enterprise '=>'',
        //'Team'=>'T:',
        'Linux'=>'L',
        'Windows'=>'W',
    	'Implementation'=>'Imp',
    	'Latin America'=>'LATAM'
    );
    $profiles = getProfileList();

    foreach($profiles as $q => $name){
            $name = str_replace(array_keys($trims),$trims,$name);
            $profiles[$q] = $name;
    }
    return $profiles;
}

function selectProfile($requested) {
    $profiles = getProfileListShort();

    if(in_array($requested,$profiles))
        return array_search($requested,$profiles);
    else if(in_array($requested,array_keys($profiles)))
        return $requested;

    return 'Enterprise All';
}

//Get data for several queues
function getQueueData($profiles) {
    $data = array();
    foreach($profiles as $profile => $name) {
        $data[$name] = getProfileData($profile);
    }
    return $data;
}

// do some postprocessing on the queue
// this is potentially a place to 
// confirm fields are there
// sanity check field data
// make shortened versions of some fields,etc
function processTest($q,$profile) {
    if(!is_array($q) || count($q)==0) return $q;
    require_once('score/score.php');
    $now = time();

    // * populate age and score fields, because they're live data
    // * shorten subject and account because some are too long
    // * cloud tickets come in with an account name of "false" .. this is stupid
    foreach ($q as $t){
        if(property_exists($t,'fepochtime'))
            $age =  $now - $t->fepochtime;
	    if(property_exists($t,'age_seconds'))
                $age = max($age,$t->age_seconds);
            $t->age_seconds = $age;
        $t->score = getScore($t,$profile);
        $t->subject = substr($t->subject, 0, 100);
        if(!property_exists($t,'aname') || $t->aname == false)
            $t->aname = $t->account;
        $t->aname = substr($t->aname, 0,30);
        $out[] = $t;
    }
    return $out;
}

function getUserPrefs($user,$field='') {
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
    $key = 'prefs:'.$user;
    $prefs = array();
    if (is_array($field))
        $prefs = $redis->hmget($key,$field);
    elseif($redis->hexists($key,$field))
        $prefs[$field] = $redis->hget($key,$field);
    else
        $prefs = $redis->hgetall($key);
    return $prefs;
}
function setUserPrefs($user,$prefs) {
    $hash = 'prefs:'.$user;
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
    $redis->hmset($hash,(array)$prefs);
}

if(isset($_REQUEST['showProfiles'])) {
    echo json_encode(getProfileListShort());
}

if(isset($_REQUEST['showFilters'])){
    require_once('filters.php');
    echo $filters;
}

if(isset($_REQUEST['userPrefs'])){
    $user = $_COOKIE['COOKIE_last_login'];
    $prefs = getUserPrefs($user,$_REQUEST['userPrefs']);
    $jsonPrefs = json_encode($prefs);
    if($jsonPrefs !== false)
        echo $jsonPrefs;
}
if(isset($_REQUEST['userPrefset']) && isset($_POST)){
    //easy to fake :/
    $user  = $_COOKIE['COOKIE_last_login'];
    $postdata = file_get_contents("php://input");
    $prefs = json_decode($postdata);
    if($prefs !== false) setUserPrefs($user,$prefs);
    else echo "error reading post, maybe not valid json?";
}

/* Process options and finalize output below this point (eg: Driver) */
if(isset($_REQUEST['queue'])) {
    $selected = selectProfile($_REQUEST['queue']);
    $queueData = getProfileData($selected);
    $queueData = processTest($queueData,$selected);

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
