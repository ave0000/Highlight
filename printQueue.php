<?php
//render a json decoded list of tickets in tabular form
function printQueue($queue) {
    $table = "<table>";
    $fields = array(
        "ticket_link",
        "age",
        "score",
        "account_link",
        "subject",
        "team",
        "status_",
        "platforms_"
    );
    
    $table .= "\t<tr>\n";
    foreach($fields as $field) {
        $table .= "\t\t<th>";
        $table .= $field;
        $table .= "</th>\n";
    }
    $table .= "\t</tr>\n";
    
    foreach($queue as $ticket) {
        $table .= "\t<tr>\n";
    
        foreach($fields as $field) {
           $table .= "\t\t<td>";
           $table .= $ticket->{$field};
           $table .= "</td>\n";
        }
        #var_dump($ticket);
        #$table .= $ticket->subject;
        $table .= "\t</tr>\n";
    }
    $table .= "</table>";
    return $table;
}
?>
