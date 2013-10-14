<?php
//describe the available filters, and their parameters
$filters = '[
        {
                "name":"Status",
                "fn":"findStatus",
                "parameters": [{"name":"Status","value":"Feedback Received"}]
        },{
                "name":"Multi-Account",
                "fn":"findMultiTicketAccounts",
                "parameters":[{"name":"Min tickets","value":4,"type":"number"}]
        },{
                "name":"Fine Wines (Aged)",
                "fn":"findAgedTickets",
                "parameters":[{"name":"Hours","value":4,"type":"number"}]
        },{
                "name":"Subject regex",
                "fn":"goAway",
                "parameters":[{"name":"Subject","value":"/^((?!OPSMGR).)*$/"}]
        },{
                "name":"Accounting",
                "fn":"accountFind",
                "parameters":[{"name":"Account","value":"AON"}]
        },{
                "name":"Severity",
                "fn":"severityFilter",
                "parameters":[{"name":"Severity","value":"Emergency","type":"multiselect","values":["Emergency","Urgent","Standard"]}]
    	},{
                "name":"Chain",
                "fn":"chainFilter",
                "parameters":[{"name":"age","value":"4"}]
    }
]';



//given a queue, find the tickets that are over 'hours' old
function findAgedTickets($queue,$hours=4) {
    if(!is_numeric($hours) || !is_array($queue)) return $queue;
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
    if($status == "" || !is_array($queue)) return $queue;
    $out = array();
    foreach($queue as $ticket)
    	if($ticket->status == $status)
            $out[] = $ticket;
    return $out;
}

//given a queue, return a queue with accounts with at least 'min_count' tickets
function findMultiTicketAccounts($queue,$min_count=4) {
    if(!is_numeric($min_count) || !is_array($queue)) return $queue;
    $out = array();

    while($queue) {
        $test = array(array_pop($queue));
        $account = $test[0]->account;

        for($i=0;$i<count($queue);$i++) {
            if($queue[$i]->account == $account) {
                $test[] = $queue[$i];
                unset($queue[$i]);
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
    if(!is_array($queue)) return $queue;

    foreach($queue as $ticket)
        if(stristr($ticket->account_link,$value)!==FALSE)
            $out[] = $ticket;

    if(count($out) < 1) {
        $foo->subject = "No results for $value";
        $out[] = $foo;
    }
    return $out;
}

function severityFilter($q,$type) {
    if(!is_array($q)) return $q;
    //$type = explode('|',$type);
    foreach($q as $t){
        if(stristr($t->sev, $type) !== false)
            $out[] = $t;
    }
    return $out;
}

function chainFilter($q,$age) {
    /*Statuses: No Feedback, Feedback Received, Closed with Feedback
Top Aged tickets (10): only qualified if the ticket is over 4 hours
New Customer Initiated tickets: from category(?).
*/
    if(!is_array($q)) return $q;
    if(!is_numeric($age)) $age = 4;
    $out = array();

    $wines = findAgedTickets($q,$age);
    $wines = array_slice($wines, 0, 10);

    $emergencies = severityFilter($q,"Emergency");
    $feedbacks = findStatus($q);
    $nofeedbacks = findStatus($q,"No Feedback");
    $closeds = findStatus($q,"Closed with feedback");


    $cust = array();
    foreach($q as $t)
            if(stristr($t->category, "customer_initiated") !== false)
                $cust[] = $t;

    $out = array_merge($feedbacks,$nofeedbacks,$closeds,$cust,$wines,$emergencies);

    return $out;
}
?>