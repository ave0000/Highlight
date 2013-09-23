<?php
/*
 * Functionality for scoring tickets
 * written by Avery Scott
 * based off of code by Keith Fralick
 *  in oneview's slick.php
 */

function scoreSort($a,$b) {
	return $a->score < $b->score;
}

// Do scoring
	//using fields:
	// subject
	// status
	// age_seconds
	// category
	// sev
	// is_cloud
	//also MAY need:
	// whatever data Modifiers call for
function getScore($t,$profile='null'){
	//maintenances to the top
    if (preg_match('/^SCHLD \d+.*/', $t->subject))
    	return '-';

	$feedBack = 'Feedback Received';
	$categoryMatch = '/.*customer_initiated.*/i';
	$subjectMatch = array( //MUST be desc sorted
		'/.*ALERT:Rackwatch.*All.*Services.*/i'=>1200,
		'/ALERT:.*SiteScope.*/i'=> 800
	);

	$base_score = 0;

    // customer feedback
    if(preg_match($categoryMatch, $t->category))
        $base_score = 600;
	elseif($t->status == $feedBack)
		$base_score = 600;

	//test for some subject strings
	foreach($subjectMatch as $s=>$score)
		if(preg_match($s,$t->subject)){
			$base_score = $score;
			break;
		}

	//create severity for cloud tickets
    if ($t->iscloud)
    	if (preg_match('/Monitoring Alert .*/', $t->subject))
			$t->sev = 'emergency';

	//bolster score with severity
	if($t->sev =='emergency')
		$points = 35;
	elseif($t->sev =='urgent')
		$points = 9;
	else
		$points = 2.25;

    // score primarily based on minutes ... at least 2.
    $minticks = ( $t->age_seconds / 60) * 2;
	$minticks = max(2,$minticks);
	
	//data collected, time to calc
    $score = $base_score + ($minticks * $points);
    if($profile)
		$score *= getScoreModifier($t,$profile);
    $score = intval($score);

	return $score;
}

// Modifier
// allows for per-queue customization
// qs is the list of queue profiles
// which MAY have a 'modifier' array
// which SHOULD be "index"->filters pairs
// filters MUST be attribute regex pairs
function getScoreModifier($ticket,$profile){
	//a possible strategy would be to build a cacheable function
	$modifier = 1;
	$mods = getScoreModifierRules($profile);

	if(!@count($mods))
		return $modifier;

    foreach($mods as $mod)
        if(isset($mod['filter']) && isset($mod['index']))
            foreach($mod['filter'] as $attribute=>$regex)
                if(isset($t->{$attribute}) && preg_match($regex, $t->{$attribute}))
                    $modifier *= $mod['index'];

	return $modifier;
}
function getScoreModifierRules($profile) {
	require_once('profile_list.inc');
	//look up profile
	if(!is_array($qs) || !isset($qs[$profile]))
		return array();
	if(!isset($qs[$profile]['modifier']))
		return array();
	$mods = $qs[$profile]['modifier'];

	if(!is_array($mods))
		$mods = array($mods);

	//remove empties
	return array_filter($mods);
}

if(isset($_REQUEST['scoreModifiers'])) {
	echo json_encode(getScoreModifierRules($_REQUEST['scoreModifiers']));
}
?>
