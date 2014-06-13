<?php
//render a json decoded list of tickets in tabular form
function printQueue($queue) {
    $table = "<table>";
    $fields = array(
        "ticket",
        "age_seconds",
        "score",
        "aname",
        "subject",
        "team",
        "status",
        "platform"
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
        $table .= "\t</tr>\n";
    }
    $table .= "</table>";
    return $table;
}

//Given a number of seconds, return a contextual string
function secs_to_str($x) {
        if ($x == 0) return 0;

        $str = "";

        if ($x >= 86400) {
                $n = intval($x / 86400);
                $x -= ($n * 86400);
                $str .= sprintf("%dd", $n);
        }

        if ($x >= 3600) {
                $n = intval($x / 3600);
                $x -= ($n * 3600);
                $str .= sprintf("%d:", $n);
        }

        if ($x > 60) {
                $n = intval($x / 60);
                $x -= ($n * 60);
                $str .= sprintf("%02d", $n);
        }

        if (!strlen($str))
                $str = $x."s";

        return $str;
}

//read a json block and turn it into a table
function summaryTable($summary) {
    $table = "<table>\n\t<tr>\n";

    $table .= "\t\t<th>Time</th>\n";
    foreach($summary->summary as $name => $profile)
        $table .= "\t\t<th>$name</th>\n";
    
    $table .= "\t</tr>\n\t<tr>\n";

    $table .= "\t\t<td rowspan=2>".date('H:i',$summary->timeStamp)."</td>\n";
    foreach($summary->summary as $profile) 
        $table .= "\t\t<td>".$profile->totalCount."</td>\n";

    $table .= "\t</tr>\n\t<tr>\n";

    foreach($summary->summary as $profile)
        $table .= "\t\t<td>".secs_to_str($profile->latency)."</td>\n";

    $table .= "\t</tr>\n</table>";
    return $table;
}
?>
