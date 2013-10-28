<?php
if(!isset($argv)) exit("CLI only");

function hashCache($redis,$obj) {
	$fields = array(
		"OnWatch",
		"account",
		"aname",
		"fepochtime",
		"age_seconds",
		"category",
		"iscloud",
		"platform",
		"sev",
		"status",
		"subject",
		"team",
		"ticket",
		);
	//var_dump($obj);
	$ticketList = array();
	$redis->multi();
	foreach($obj as $ticket){
		$store = array();
		foreach($fields as $field) {
			if(property_exists($ticket,$field))
				$store[$field] = $ticket->{$field};
		}
		$redis->hMSet('ticket:'.$ticket->ticket,$store);
		$ticketList[] = $ticket->ticket;
	}
	$redis->exec();
	return $ticketList;
}

function saveTicketList($redis,$name,$data){
    $name = 'ticketList:'.$name;
    $data = json_encode($data);
    $now  = round((microtime(true) * 1000));
    $redis->set($name, $data);
    $redis->set($name.":timestamp",$now);

    $redis->publish($name,$data);
}

function saveSummary($redis,$name,$data){
	$jdata = json_encode($data);
	$redis->set($name,$jdata);
	$redis->publish('update'.$name,$jdata);
}

function getCache($redis,$profile,$latency) {
	$latstr = "latency_"; //ability to take string OR bare number
	if(strpos($latency,$latstr)===false)
		$latency = $latstr.$latency;

	$cacheProfile = "summary".$profile.$latency;

	//hitting oneview is apparently very expensive
	$slick = 'http://oneview.rackspace.com/slick.php';
	$url = $slick . '?fmt=json&latency='.$latency.'&profile=' . urlencode($profile);
	echo "getting $url\n";
	
	$contents = file_get_contents($url);
	$contents = json_decode($contents);
	$data = $contents;//->queue;
	$sum = $contents->summary;

	$ticketList = hashCache($redis,$data->queue);
	saveTicketList($redis,$profile,$ticketList);

	$out->profile = $profile;
	$out->totalCount = $sum->total_count;
	$out->latency = $sum->{$latency};
	$out->timeStamp = round( (microtime(true) * 1000) );

	saveSummary($redis,$cacheProfile,$out);
	
	return $out;
}

/*
subscribe to the update request channel
when a request comes in, fetch it from oneview
making sure we're only doing one request at a time
*/
ini_set('default_socket_timeout', -1);
set_time_limit(0);
//$address = '127.0.0.1';
$address = '/tmp/redis.sock';

$keepGoing = true;

$redis = new Redis();
$redis->pconnect($address);

function isFresh($entry,$limit=5000) {
	if($entry === false) return false;
	//if(!property_exists($entry,'timeStamp')) return false;
	$now = round((microtime(true) * 1000));
	$diff = $now - $entry;
	if($diff > $limit) return false;
	echo "*";
	return true;
}

while($keepGoing) {
	try{
	//prioritize queue requests over summary requests
	while(false !== ($entry = $redis->lPop('wantNewQueue'))){
		if($redis->get($entry) === false){
			//echo "queue requested: ".$entry;
			getCache($redis,$entry,'latency_1');
			//echo "--got\n";
		}
	}

	$popped = $redis->blpop('wantNewSummary',0);
	$entry = $popped[1];
	if($entry != "" && !isFresh($redis->get($entry.':timestamp'))){
		//summary:7
		$profile = substr($entry,7);
		$latency = strstr($profile,'latency');
		$profile = strstr($profile,'latency',true);
		if(!isFresh($redis->get('ticketList:'.$profile.':timestamp')))
			getCache($redis,$profile,$latency);
	}
}catch(Exception $e){
		echo "Reconnecting\n";
		sleep(1);
		$redis = new Redis();
		$redis->pconnect($address);
	}
}

?>
