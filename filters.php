<?php
//describe the available filters, and their parameters
$filters = '[
        {
                "name":"Feedback Received",
                "fn":"findStatus",
                "parameters": [{"name":"Status","value":"Feedback Received"}]
        },{
                "name":"Multi-Account",
                "fn":"findMultiTicketAccounts",
                "parameters":[{"name":"Min tickets","value":4}]
        },{
                "name":"Fine Wines (Aged)",
                "fn":"findAgedTickets",
                "parameters":[{"name":"hours","value":4}]
        },{
                "name":"Subject regex",
                "fn":"goAway",
                "parameters":[{"name":"subject","value":"/^((?!OPSMGR).)*$/"}]
        },{
                "name":"Accounting",
                "fn":"accountFind",
                "parameters":[{"name":"account","value":"AON"}]
        },{
                "name":"Severity",
                "fn":"severityFilter",
                "parameters":[{"name":"Severity","value":"Emergency"}]
	}
]';



//given a queue, find the tickets that are over 'hours' old
function findAgedTickets($queue,$hours=4) {
    $out = array();
    $min_seconds = $hours * 3600;

    foreach($queue as $ticket) {
        if($ticket->age_seconds >= $min_seconds) {
            $out[] = $ticket;
        }
    }
    return $out;
}


//given a queue, return a queue of tickets with a certain 'status'
function findStatus($queue,$status="Feedback Received") {
    $out = array();
    foreach($queue as $ticket)
    	if($ticket->status == $status)
		$out[] = $ticket;
    return $out;
}

//given a queue, return a queue with accounts with at least 'min_count' tickets
function findMultiTicketAccounts($tickets,$min_count=4) {
    $out = array();

    while($tickets) {
        $test = array(array_pop($tickets));
        $account = $test[0]->account;

        for($i=0;$i<count($tickets);$i++) {
            if($tickets[$i]->account == $account) {
                $test[] = $tickets[$i];
                unset($tickets[$i]);
            }
        }
        if(count($test)>=$min_count)
            $out=array_merge($out,$test);
    }

    return $out;
}

//not sure if there's demand for this on the windows side
function goAway($q,$type='/^((?!OPSMGR).)*$/'){
    if($type == "" || !is_array($q)) return $q;
    foreach($q as $ticket)
        if(preg_match($type,$ticket->subject))
            $out[] = $ticket;

    return $out;
}

function accountFind($queue,$value) {
    if($value == "") return $queue;

    foreach($queue as $ticket)
        if(stristr($ticket->account_link,$value)!==FALSE)
            $out[] = $ticket;

    if(count($out) < 1) {
        $foo->subject = "No results for $value";
        $out[] = $foo;
    }
    return $out;
}

function severityFilter($q,$type="Emergency") {
    if($type == "" || !is_array($q)) return $q;
    //$type = explode('|',$type);
    foreach($q as $t){
        if(stristr($t->sev, $type) !== false)
            $out[] = $t;
    }
    return $out;
}
?>
