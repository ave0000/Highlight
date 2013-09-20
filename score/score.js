app.filter('score',function(){
        return function(t) {
            if (/^SCHLD \d+.*/.test(t.subject))
                return '-';
            var feedBack = 'Feedback Received';
            var points = 0;
            var base_score = 0;

                        // customer feedback
            if(/.*ALERT:Rackwatch.*All.*Services.*/i.test(t.subject))
                base_score = 1200;
            else if(/ALERT:.*SiteScope.*/i.test(t.subject))
                base_score = 800;
            else if(/.*customer_initiated.*/i.test(t.category))
                base_score = 600;
            else if(t.status == feedBack)
                base_score = 600;

            //create severity for cloud tickets
            if (t.iscloud)
                if (/Monitoring Alert .*/.test(t.subject))
                    t.sev = 'emergency';

            //bolster score with severity
            if(t.sev =='emergency')
                points = 35;
            else if(t.sev =='urgent')
                points = 9;
            else
                points = 2.25;

            // score primarily based on minutes ... at least 2.
            var minticks = ( t.age_seconds / 60) * 2;
            minticks = Math.max(2,minticks);

            //data collected, time to calc
            var score = base_score + (minticks * points);
            //if($profile)
            //    $score *= getScoreModifier($t,$profile);
            score = parseInt(score);

            return score;
        }
    }
);