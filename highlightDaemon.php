<?php
if(!isset($argv)) exit("CLI only");

function saveProfile($name,$data){
    $redis = new Redis();
    $redis->pconnect('127.0.0.1', 6379);
    $data = serialize($data);
    $redis->set($name, $data);
    $redis->expire($name, 60);
    $redis->publish('updateProfile'.$name,$name);
}
function saveSummary($name,$data){
	$redis = new Redis();
	$redis->pconnect('127.0.0.1', 6379);
	//$data = json_decode($data);
	$data = serialize($data);
	$redis->set($name, $data);
	$redis->expire($name, 30);
	$redis->publish('update'.$name,$data);
}

function getCache($profile,$latency) {
	$latstr = "latency_"; //ability to take string OR bare number
	if(strpos($latency,$latstr)===false)
		$latency = $latstr.$latency;

	$cacheProfile = "summary".$profile.$latency;

	//hitting oneview is apparently very expensive
	$slick = 'http://oneview.rackspace.com/slick.php';
	$url = $slick . '?fmt=json&latency='.$latency.'.&profile=' . urlencode($profile);
	
	$contents = file_get_contents($url);
	$contents = json_decode($contents);
	$data = $contents;//->queue;
	$sum = $contents->summary;

	$out->profile = $profile;
	$out->totalCount = $sum->total_count;
	$out->latency = $sum->{$latency};

	saveProfile($profile,$data);
	saveSummary($cacheProfile,$out);
	return $out;
}

/*
subscribe to the update request channel
when a request comes in, fetch it from oneview
making sure we're only doing one request at a time
*/
ini_set('default_socket_timeout', -1);
set_time_limit(0);

$keepGoing = true;

$redis = new Redis();
$redis->pconnect('127.0.0.1',6379);

while($keepGoing) {
	//prioritize queue requests over summary requests
	while(false !== ($entry = $redis->lPop('wantNewQueue'))){
		if($redis->get($entry) === false){
			//echo "queue requested: ".$entry;
			getCache($entry,'latency_1');
			//echo "--got\n";
		}
	}

	$popped = $redis->blpop('wantNewSummary',0);
	$entry = $popped[1];
	if($entry != "" && ($redis->get($entry) === false)){
		//summary:7
		$profile = substr($entry,7);
		$latency = strstr($profile,'latency');
		$profile = strstr($profile,'latency',true);
		//echo "getting '".$profile."' with ".$latency;
		getCache($profile,$latency);
		//echo "--got\n";
		//sleep(1);
	}
	//echo $redis->llen('wantNewSummary')."\n";
}

?>
