<?php
/*
 * Functionality for scoring tickets
 * written by Avery Scott
 * based off of code by Keith Fralick
 *  in oneview's slick.php
 */

function score_sort($a,$b) {
        return $a->score < $b->score;
}

// Do scoring
	//using fields:
	// subject
	// status
	// age_seconds
	// category
	//also need:
	// whatever data Modifiers call for

function getScore($ticket){
        $d = $ticket;

	//maintenances to the top
        if (preg_match('/^SCHLD \d+.*/', $d->subject))
        	return '-';

        $base_score = 0;
	$feedBack = 'Feedback Received';

	$subject_scores = array(
		'/.*ALERT:Rackwatch.*All.*Services.*/i'=>1200,
		'/ALERT:.*SiteScope.*/i'=>800
	);

	//test for some subject strings
	foreach($subject_scores as $s=>$score){
		if(preg_match($s,$d->subject))
			$base_score += $score;
	}


        // These get a high base score!
        if(preg_match('/.*customer_initiated.*/i', $d['category']))
                $base_score += 600;
	else if($d->status == $feedBack){
		$base_score += 600;
	}

	// cloudy cloud cloud
        $points = 2.25;
        if ($d->iscloud) {
        	if (preg_match('/Monitoring Alert .*/', $d->subject))
			$points = 35;
        }

        // score primarily based on time, 
	// at least two minutes
        $minticks = ( $d->age_seconds / 60) * 2;
	$minticks = max(2,$minticks);

	//madness
	$modifier = getScoreModifier($d);


        $score = $base_score + ($minticks * $points);
	$score *= $modifier;
        $score = intval($score);

	return $score;
}

// Modifier
// allows for per-queue customization
// qs is the list of queue profiles
// some of them have a 'modifier' array
// modifier arrays SHOULD be pairs of index and filters
// filters are attribute regex pairs
// treating qs as user input

function getScoreModifier($ticket,$profile){
	$modifier = 1;

	//look up profile
	if(is_array($qs) && isset($qs[$profile]) && isset($qs[$profile]['modifier'])) {
		$mods = $qs[$profile]['modifier'];
	}else
		return $modifier;

	if(!is_array($mods))
		$mods = array($mods);

	//remove empties
	$mods = array_filter($mods);

	//if something's wrong, bail out
	if(!@count($mods))
		return $modifier;

        foreach($mods as $mod) {
                if(isset($mod['filter']) && isset($mod['index'])) {
                        foreach($mod['filter'] as $attribute=>$regex) {
                                if(isset($d->{$attribute}) && preg_match($regex, $d->{$attribute})) {
                                        $modifier *= $mod['index'];
				}
                        }
                }
        }

	return $modifier;
}

?>
