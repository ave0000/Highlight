<?php

function getCachedSummary($profile) {
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
    $boop = $redis->get($profile);
    if($boop === false) {
        $redis->rpush('wantNewSummary',$profile);
        return "try again soon";
    }
    return json_decode($boop);
}

//given a profile and a latency depth
//return a summary consisting of
//	profile
//	totalCount
//	latency
function getSummary($profile,$latency=-1) {
	if($latency==-1)
		$latency = figureOutLatency($profile);
	$latstr = "latency_"; //ability to take string OR bare number
	if(strpos($latency,$latstr)===false)
		$latency = $latstr.$latency;

	$cacheProfile = 'summary:'.$profile.':'.$latency;

	if(( $hasCache = getCachedSummary($cacheProfile) ) !== false)
	        return $hasCache;
	else return array();
}

//return a list of queues that there should be summaries for
function getSummaryList() {
    require_once('jtable.php');
    $profiles = getProfileListShort();

    foreach($profiles as $q => $name){
    	$profile = new stdClass();
		$profile->profile = $q;
		$profile->shortName = $name;
		$profile->latencyCount = figureOutLatency($profile->profile);
		$out[] = $profile;
		unset($profile);
    }
    return $out;
}

//based on https://wiki.rackspace.corp/Enterprise/tvdisplay
//why can't they all just be "All"?
function figureOutLatency($name) {
	//assume table is sorted by priority,
	//take the first match
	$latencies = array(
		'Team'=>10,
		'ARIC'=>1,
		'Cloud'=>10,
		'Linux'=>15,
		'Windows'=>15,
		'All'=>20
	);
	foreach($latencies as $find=>$total){
		if(strpos($name,$find)!==false)
			return $total;
	}
	return 15;
}

if(isset($_REQUEST['summaryProfiles'])) {
	$stuff = getSummaryList();
    echo json_encode($stuff);
}elseif(isset($_REQUEST['summary'])){
    if(isset($_REQUEST['latency']))
		$latency = $_REQUEST['latency'];
	else
		$latency = -1;
    $boop = getSummary($_REQUEST['summary'],$latency);
    echo json_encode($boop);
}
?>