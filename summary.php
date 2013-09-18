<?php

function saveCachedSummary($data,$name='cache'){
	$name = 'summary'.$name;
        $redis = new Redis();
        $redis->pconnect('127.0.0.1', 6379);
        //$data = json_decode($data);
        $data = serialize($data);
        $redis->set($name, $data);
        $redis->expire($name, 15);
}

function getCachedSummary($profile) {
	$profile = 'summary'.$profile;
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
        $boop = $redis->get($profile);
        return unserialize($boop);
}


function getSummary($profile,$latency) {
	if( ($hasCache = getCachedSummary($profile) ) !== false)
		return $hasCache;
	$slick = 'http://oneview.rackspace.com/slick.php';
	$url = $slick . '?fmt=json&latency='.$latency.'.&profile=' . urlencode($profile);
	
	$contents = file_get_contents($url);
	$contents = json_decode($contents);
	$contents = $contents->summary;
	$out->profile = $profile;
	$out->totalCount = $contents->total_count;
	$out->latency = $contents->{$latency};

	saveCachedSummary($out,$profile);
	return $out;
}

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
	//	echo "checking $name for $find if found giving $total\n";
		if(strpos($name,$find)!==false)
			return $total;
	}
	return 15;
}

if(isset($_REQUEST['summaryProfiles'])) {
	$stuff = getSummaryList();
    echo json_encode($stuff);
}elseif(isset($_REQUEST['summary'])){
    $boop = getSummary($_REQUEST['summary'],$_REQUEST['latency']);
    echo json_encode($boop);
}
?>
