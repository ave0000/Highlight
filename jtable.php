<?php

function saveCache($data,$name='cache'){
	$redis = new Redis();
	$redis->pconnect('127.0.0.1', 6379);
	//$data = json_encode($data);
	$data = serialize($data);
	$redis->set($name, $data);
	$redis->expire($name, 60);
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

function getTicketList($redis,$profile) {
    $listName = 'ticketList:'.$profile;
    $list = $redis->get($listName);

    if($list === false || $list == false ) {
	echo $list;
        $redis->rpush('wantNewQueue',$profile);
        return "try again soon";
    }

    return json_decode($list)->tickets;
}

function getProfileData($profile) {
    $redis = new Redis();
    $redis->pconnect('127.0.0.1',6379);

    $list = getTicketList($redis,$profile);

    if(!$list || !is_array($list) )
        return $profile.":".$list;

    $out = array(); 
    foreach($list as $t)
        $out[] = (object) $redis->hgetall('ticket:'.$t);
    return $out;
}

//a list of profiles, minus the ones we don't want
function getProfileList(){
    return array_keys(getProfileListShort());


    if(false !== ($hasCache = getCachedProfile('profileList'))) 
        return $hasCache;

    include('profile_list.inc');
    $out = array();
    $profiles = array_keys($qs);
    $excludes = array('Corp','Strat','Priority','Testing');
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
    require_once('summary.php');
    $map = getSummaryList();
    $out = array();
    foreach($map as $q) {
        $out[$q->profile] = $q->filter;
    }
    return $out;

    $pecans = 'http://pecan-api.res.rackspace.com/api/v1/allqueues';
    $views = json_decode(file_get_contents($pecans))->summary;

    $out = array();
    foreach($views as $short=>$list) {
	if(isset($list[1]))
		$index = $list[1];
	elseif(isset($list[0]))
		$index = $list[0];
	else
		$index = $list;
        $out[$index] = $short;
    }

   return $out;


	require_once('summary.php');
    $trims = array(
        'Enterprise '=>'',
        //'Team'=>'T:',
        'Linux'=>'L',
        'Windows'=>'W',
	'Team '=>'',
	'Only'=>'',
    	'Implementation'=>'Imp',
    	'Latin America'=>'LATAM',
	'Critical Sites'=>'CAS',
	'DBA - '=>'',
	'Storage - '=>'',
	'Virtualization'=>'Virt',
	'Segment '=>'Seg',
	'Support'=>'Sup'
    );
    $profiles = getProfileList();

    foreach($profileJson as $name=>$short){
            //$name = str_replace(array_keys($trims),$trims,$q->profile);
            $profiles[$name] = $short;
    }
    return $profiles;
}

function selectProfile($requested) {
    require_once('summary.php');
    $profiles = getSummaryList();
    //var_dump($profiles);
    foreach ($profiles as $p) {
        if(
            $requested == $p->profile ||
            $requested == $p->shortName ||
            $requested == $p->filter 
        )
            return $p->profile;
    }

    /*
    if(in_array($requested,$profiles))
        return array_search($requested,$profiles);
    else if(in_array($requested,array_keys($profiles)))
        return $requested;
    */
    echo '"'.$requested.'"';

    return 'Enterprise All';
}

// do some postprocessing on the queue
// this is potentially a place to 
// confirm fields are there
// sanity check field data
// make shortened versions of some fields,etc
function processTest($q,$profile) {
    if(!is_array($q) || count($q)==0) return $q;
    $out = array();
    require_once('score/score.php');
    $now = time();

    // * populate age and score fields, because they're live data
    // * shorten subject and account because some are too long
    foreach ($q as $t){
        /*
        if(property_exists($t,'fepochtime'))
            $age =  $now - $t->fepochtime;
	    if(property_exists($t,'age_seconds'))
                $age = max($age,$t->age_seconds);
            $t->age_seconds = $age;
        */

        $t->score = getScore($t,$profile);
        $t->subject = substr($t->subject, 0, 100);
        if(!property_exists($t,'aname') || $t->aname == false || $t->aname == 'null')
            $t->aname = $t->account;
        //$t->aname = substr($t->aname, 0,30);
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
    if(isset($_COOKIE['COOKIE_last_login']))
        $user = $_COOKIE['COOKIE_last_login'];
    else $user = '';
    $prefs = getUserPrefs($user,$_REQUEST['userPrefs']);
    $jsonPrefs = json_encode($prefs);
    if($jsonPrefs !== false)
        echo $jsonPrefs;
}
if(isset($_REQUEST['userPrefset']) && isset($_POST) &&isset($_COOKIE['COOKIE_last_login']) ){
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
