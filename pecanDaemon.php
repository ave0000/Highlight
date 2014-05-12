<?php
if(!isset($argv)) exit("CLI only");

function hashCache($redis,$obj) {
	$fields = array(
		"watchlist"=>"OnWatch",
		"account_number"=>"account",
		"account_name"=>"aname",
		//"minutes"=>"age_seconds",
		"category"=>"category",
		"linux"=>"linux",
		"windows"=>"windows",
		"severity"=>"sev",
		"status"=>"status",
		"subject"=>"subject",
		"support_team"=>"team",
		"_id"=>"ticket",
	);
	//var_dump($obj);
	$ticketList = array();
	$redis->multi();
	foreach($obj as $ticket){
		$store = array();
		foreach($fields as $field=>$map) {
			if(property_exists($ticket,$field))
				$store[$map] = $ticket->{$field};
		}
		if(property_exists($ticket,'minutes'))
			$store['age_seconds'] = $ticket->minutes * 60; 
		$store['iscloud'] = (strpos($ticket->_id,'-')!==false)?0:1;
		$redis->hMSet('ticket:'.$store['ticket'],$store);
		$ticketList[] = $store['ticket'];
	}
	$redis->exec();
	return $ticketList;
}

function saveWithStamp($redis,$name,$data) {
	$now  = round((microtime(true) * 1000));
	$data->timestamp = $now;
	$json = json_encode($data);

	$redis->set($name,$json);
	$redis->set($name.':timestamp',$now);
	$redis->publish($name,$json);
}

//helper function: json decodes a get
function jget($url) {
	$file = file_get_contents($url);
	if($file == '') return false;
	$json = json_decode($file);
	return $json;
}

function getCache($redis,$profile) {
	echo "getting $profile ";

	$map = jget('/var/www/html/highlight/profile_list.inc');
	$summary = '';
	foreach($map as $info)
		if(is_object($info))
			if($info->profile == $profile)
				$summary = $info->filter;
			else if($info->filter == $profile)
				$summary = $info->filter;

	if($summary == ''){
		echo "Could not map profile: '". $profile ."'\n";
		return false;
	}

	$slick = 'http://localhost/pecan-api/api/v1';
	$queueUrl = $slick . '/tickets/filter/' .urlencode($summary);
	echo ": $queueUrl...";

	$contents = jget($queueUrl);
	$qlatency = jget($queueUrl.'/latency');
	echo "got...";

	$out = new stdClass();
	$out->profile = $profile;

	if(@property_exists($contents,'message')) { // pecan sends an annoying message on an empty queue
		if(strpos($contents->message,"Nothing to Report")!==false) {
			echo "empty ...";
			$contents->tickets = array();
		}
	}

	if(@property_exists($contents,'tickets')) {
		$contents->tickets = hashCache($redis,$contents->tickets);
		saveWithStamp($redis,'ticketList:'.$profile,$contents);
		$out->totalCount = count($contents->tickets);
	}

	if(@property_exists($qlatency,'latency'))
		$out->latency = round($qlatency->latency * 60);

	$cacheProfile = "summary:".$profile;
	saveWithStamp($redis,$cacheProfile,$out);
	echo "saved\n";
	
	return $out;
}

ini_set('default_socket_timeout', -1);
set_time_limit(0);
//$address = '127.0.0.1';
$address = '/var/run/redis/redis.sock';

$keepGoing = true;

$redis = new Redis();
$redis->pconnect($address);

//true if $timestamp less than $limit miliseconds old
function isFresh($timestamp,$limit=5000) {
	if(!$timestamp) return false;
	$now = round((microtime(true) * 1000));
	$diff = $now - $timestamp;
	return ($diff <= $limit);
}

while($keepGoing) {
	try{
		$popped = $redis->blpop('wantNewSummary',0);
		$entry = $popped[1];
		if($entry != ''){
			$splitted = split(':',$entry);
			$profile = $splitted[1];
			if(!isFresh($redis->get($entry.':timestamp')))
				getCache($redis,$profile);
			else echo '*';
		}
	}catch(Exception $e){
			echo "Reconnecting to Redis in 1s\n";
			sleep(1);
			$redis = new Redis();
			$redis->pconnect($address);
		}
}

?>
