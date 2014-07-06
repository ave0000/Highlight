function baseScore(t) {
    var base_score = 1;

    if(t.subject == undefined)
       return undefined;
    var feedBack = 'Feedback Received';

    var sub = t['subject'];
    var cat = t['category'];

    // customer feedback
    if(/.*ALERT:Rackwatch.*All.*Services.*/i.test(sub))
        base_score = 1200;
    else if(/ALERT:.*SiteScope.*/i.test(sub))
        base_score = 800;
    else if (/Monitoring Alert .*/.test(sub))
        base_score = 700
    else if(/.*customer_initiated.*/i.test(cat))
        base_score = 600;
    else if(t.status == feedBack)
        base_score = 600;
    else
    return base_score;
    return base_score/100;
}

function ticketScore(t) {
    if (/^SCHLD \d+.*/.test(t.subject))
        return '-';

    if(t.base_score == undefined) 
        t.base_score = baseScore(t);

    var points = 2.25;
    if(t.severity =='emergency')
        points = 35;
    else if(t.severity =='urgent')
        points = 9;

    // score is primarily based on ticket age ...
    // this can go negative if the server time is ahead of client time
    var age = (Date.now() - t.intime) / 1000 / 60;

    //data collected, time to calc
    var score = t.base_score + (2 * age * points);
    //var score = (t.base_score + ( points  * Math.log(Date.now() - t.intime) ) );
    //if($profile)
    //    $score *= getScoreModifier($t,$profile);

    return score |0 ;
}
