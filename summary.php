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
	//return json_decode(file_get_contents('profile_list.inc'));
    $pecans = 'http://pecan-api.res.rackspace.com/api/v1/summary';
    $pecans = json_decode(file_get_contents($pecans))->summary;

    foreach($pecans as $q){
    	$q->filter = figureOutFilter($q->profile);
		$out[] = $q;
    }
    return $out;
}

function figureOutFilter($name) {
	$filter = $name; //prepopulate in case we have no idea
	$name = strtolower($name); //make life easier

	if(strpos($name,'enterprise ')!==false) {
		if(strpos($name,'aric')!==false)
			return 'aric';
		if(strpos($name,'cloud')!==false)
			return 'cloud';
		$filter = str_replace('enterprise ','ent+',$name);
		$filter = str_replace('f1000','f1k',$filter);
		$filter = str_replace(' only','',$filter);
		$filter = str_replace(' (','+',$filter);
		$filter = str_replace(')','',$filter);
		return $filter;
	}

	//Teams need explicit platform filters because damn you.
	//seriously ... why can't this come from the api?
	$linuxTeams = array('n','o','p','v','x');
	if(strpos($name,'team')!==false) {
		$filter = str_replace("team ",'',$name);
		if(strpos($name,' - ')!==false) {
			$filter = str_replace(' - ','+',$filter);
		}else if(in_array( $filter, $linuxTeams)) {
			$filter .= '+linux';
		}else{
			$filter .= '+windows';
		}
		$filter = 'ent+'.$filter;
		return $filter;
	}

	if(strpos($name,'dba - ')!==false) {
		$filter = str_replace('dba - ','',$name);
		return $filter;
	}

	$knowns = array(
			'virtualization'=>'virt',
			'critical sites'=>'cas',
			'cloudbuilders support'=>'nova',
			'dba - '=>'',
			'storage - '=>'',
			'segment support'=>'segsupp',
		);
	foreach($knowns as $known=>$fix){
		if(strpos($name,$known)!==false) {
			$filter = str_replace($known,$fix,$name);
			return $filter;
		}
	}

	return 'broken';
}

//based on https://wiki.rackspace.corp/Enterprise/tvdisplay
function figureOutLatency($name) {
	//table is sorted by priority,
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
