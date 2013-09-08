<?php
//render a json decoded list of tickets in tabular form
function printTable($queue) {
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
    $fields = array(
    	"business_unit",
    	"name",
//    	"updated_at",
    	"number",
//    	"id",
    	"description",
//    	"geographic_location_id",
//    	"publish",
//    	"did",
//    	"comment",
//    	"created_at"
    );
    
    $table .= "\t<tr>\n";
    foreach($fields as $field) {
        $table .= "\t\t<th>";
        $table .= $field;
        $table .= "</th>\n";
    }
    $table .= "\t</tr>\n";

    foreach($queue as $tick) {
	$ticket = $tick->vdn;
        $table .= "\t<tr>\n";
    
        foreach($fields as $field) {
           $table .= "\t\t<td>";
           $table .= $ticket->{$field};
           $table .= "</td>\n";
        }
        $table .= "\t</tr>\n";
    }
    $table .= "</table>";
    return $table;
}
?>
