"use strict";
var serverHost = 'highlight.res.rackspace.com';
var redisHost = serverHost+':3000/';

var app = angular.module('myApp', []);

//timceCalc runs a lot, needs to be efficient
//jsperfs indicate + for concat
//some indicate that |0 is best for rounding
app.filter('timeCalc', function() {
    return function(secs) {
        if(secs % 1 !== 0) return '?';
        var mins = secs%60;
        return (secs/3600 |0) + (mins%60 < 10 ? ':0' : ':') + mins%60;
    }
});

app.filter('summaryColor',function(){
    return function(secs) {
        if(secs % 1 !== 0 || secs < 10800)
            return 'green';
        else if (secs < 21600)
            return 'yellow';
        else
            return 'red';
    }
});

app.filter('noSpaces',function(){
	return function(s){
        if(!s || !s.replace) return s;
		else return s.replace(/ /g,'');
	}
});

//service to handle saving and loading preferences
app.service('pref',function($http){
    var prefurl='jtable.php';
    var pref = this;//so i can call myself
    this.cache = new Array();

    //set a single preference value
    this.save = function(key,val){
        if(pref.cache[key] && pref.cache[key] == val) return true;

        console.log('saving '+key+' as:"'+val+'"');
        //maybe these should be buffered into blocks...
        var prefs = {last: Date.now()};
        prefs[key] = val;
        $http({
            method: 'POST',
            url: prefurl+'?userPrefset',
            data: prefs,
        });
    }
    //populate a single preference value once
    this.get = function(key,$scope) {
        return $http.get(prefurl+'?userPrefs='+key)
            .then(function (response) {
                if(response.data && response.data[key])
                    pref.cache[key] = $scope[key] = response.data[key];
            });
    };
    //watch a variable in the given scope for changes
    this.watch = function(key,$scope) {
        pref.get(key,$scope);
        $scope.$watch(key, function(newval, oldval) {
            if (newval!=undefined && newval !== oldval)
                pref.save(key,newval);
        });
    }
});


function Summary($scope, $http, $timeout,pref) {
    $scope.hideSummary = false;
    $scope.refreshTime = 30;
    $scope.summaries = {};

    //listen for published updates so that we can avoid unneeded refreshes
    var jsonSocket;
    function pubSocket() {
        jsonSocket = new WebSocket("ws://"+redisHost);
        jsonSocket.onopen = function() {
            this.send(JSON.stringify(["PSUBSCRIBE", "updatesummary*"]));
            console.log("WebSocket connected and subscribed to summary updates.");
        };
        jsonSocket.onmessage = function(message) {
            var data = message.data;
            //sanity check and then apply the message
            try{var sub = JSON.parse(data);}
            catch(e) {/*cool story*/}
            if(sub && sub.profile)
                $scope.gotQueue(sub);
            else
                console.log("JSON received:", data);
        };
        jsonSocket.onclose = function(a) {
            console.log(a);
            setTimeout(function(){pubSocket()},4000);
        }   
    }
    pubSocket();

    var requestSocket;
    function reqSocket(data) {//polling, more or less.
        requestSocket = new WebSocket("ws://"+redisHost);
        requestSocket.onopen = function() {
            data.forEach($scope.loadQueue);
        };
        requestSocket.onmessage = function(msg) {
            var data = JSON.parse(msg.data)
            if(data.profile)
                $scope.gotQueue(data);
        };
        requestSocket.onclose = function(a) {
            if($scope.hideSummary == true ) return true;
            console.log(a);
            setTimeout(function(){reqSocket(data)},4000);
        }   
    }
    
    $scope.gotQueue = function(queue) {
        queue = angular.extend($scope.summaries[queue.profile],queue);
        //if we're not in a digest/apply cycle, start one
        if(!$scope.$$phase) $scope.$apply();
        //schedule the next poll
        $scope.loadQueue(queue);
    }
    $scope.loadQueue = function(queue) {
        if(requestSocket.readyState != WebSocket.OPEN) return false;

        var retryIn = $scope.refreshTime*1000;
        var queueStr = 'summary'+queue.profile+'latency_'+queue.latencyCount;

        if(!queue.timeStamp) {//first run won't be populated
            requestSocket.send(JSON.stringify(["GET", queueStr]));
        }else{
            var age = Date.now() - queue.timeStamp;
            if(age < retryIn) {
                var diff = retryIn - age; //reschedule
                //console.log(queue.profile+'too early for refresh: '+age+' trying again in '+diff);
                $timeout.cancel(queue.timeout);
                queue.timeout = $timeout(function () {$scope.loadQueue(queue)}, diff);
            }else{
                //console.log(queue.profile+' data is '+age/1000+' seconds old, requesting new');
                requestSocket.send(JSON.stringify(["rpush","wantNewSummary",queueStr]))
            }
        }
    };
    //fetch the list of profiles to render
    //instead, we could have a list of user selected profiles to watch
    $scope.loadData = function() {
        var httpRequest = $http.get('summary.php?summaryProfiles')
        .success(function(data){
            data.forEach(function(queue){//create cards
                $scope.summaries[queue.profile] = queue;
            });
            reqSocket(data);//fire up the socket engine
        });
    };

    pref.watch('hideSummary',$scope);
    $scope.$watch('hideSummary', function(newval,oldval){
        if(newval == false && oldval== true)
            $scope.loadData();
        else if(newval == true && requestSocket) {
            requestSocket.close();
        }
    });
    //using a timeout to not block further rendering
    if($scope.hideSummary == false) $timeout($scope.loadData,0);
}

function Dynamic($scope, $http, $timeout, pref) {
    $scope.reverse = true;
    $scope.predicate = 'Score';

    $scope.queueRefreshTime = 30;
    $scope.feedbacks = [];
    $scope.queueList = [];

    pref.watch('queueListSelect',$scope);
    //pref.watch('filterListSelect',$scope);
    pref.watch('filterSearch',$scope);
    pref.watch('queueRefreshTime',$scope);
    pref.watch('predicate',$scope);
    pref.watch('reverse',$scope);

    //refresh the queue when any of these are changed (blindly, but buffered)
    $scope.$watch('queueListSelect + queueRefreshTime + filterListSelect', 
        function(){$scope.changeRefresh();} );

    $scope.$watch('showingTickets.length',function(len) {
        if(len != undefined)
            window.parent.document.title = len + ' - Highlight';
    });

    $scope.getQueueList = function() {
        $scope.queueList = '[{"Loading Options","Loading"}]';
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?showProfiles',
            cache: true,
        }).success(function(data, status) {
            $scope.queueList = data;
        });
    }
    $scope.getFilterList = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?showFilters',
            cache: true,
        }).success(function(data, status) {
            $scope.filterList = data;
        });
        $scope.filterList = '[Loading]';
    };

    $scope.changeRefresh = function() {
        //modifications buffer
        $timeout.cancel($scope.refreshTimeTimer);
        $scope.refreshTimeTimer = $timeout($scope.loadFeedback,300);
    }

    $scope.processTickets = function(data) {
        data.forEach(function(t) {

        if(t.iscloud == "1") {
            if(!t.account_link) t.account_link = "";
            var ticket = t.ticket.replace('ZEN_','');
            var account = (''+t.account_link).replace('DDI ','');
            t.aname = t.account_link;

            t.ticketUrl='https://rackspacecloud.zendesk.com/tickets/'+ticket;
            t.accountUrl='https://us.cloudcontrol.rackspacecloud.com/customer/'+account+'/servers';
        }else{
            t.ticketUrl='https://core.rackspace.com/ticket/'+t.ticket;
            t.accountUrl='https://core.rackspace.com/account/'+t.account;
        }
        });
        $scope.feedbacks = data;
    }

    $scope.loadFeedback = function() {
	//return true;
        $timeout.cancel($scope.timeOutHolder);
        var options = 'queue';
        if($scope.queueListSelect != undefined)
            options = options+'='+$scope.queueListSelect;
        if($scope.filterListSelect != undefined) {
            options = options+'&filter='+$scope.filterListSelect.name;
            // TODO: if filterListSelect option is different than filterList option then ...
            if($scope.filterListSelect.parameters != undefined){
                options = options+'&filterOpt='+$scope.filterListSelect.parameters[0].value;
            }
        }
        $scope.gettingFeedback = true;
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?'+options,
        }).success(function(data, status) {
            var retryIn = $scope.queueRefreshTime*1000;
            if(data == '"try again soon"') retryIn = 500;
            else if(!data || !(data instanceof Array))
                data = [{"subject":"None"}];
            else{
                $scope.processTickets(data);
                //$scope.feedbacks = data;
                $scope.gettingFeedback = false;

            }
            $scope.timeOutHolder = $timeout($scope.loadFeedback, retryIn);
        });
    };

    var sortAge = function(t) {return parseInt(t.age_seconds,10);};
    var sortScore = function(t) {return (t.score=='-')?9999999:parseInt(t.score,10);};
    var sortPlatform = function(t) {return t.platform;};
    var sortSev = function(t) {
        if(t.sev == 'emergency') return 9000;
        else if(t.sev == 'urgent') return 1000;
        else return 0;
    };

    //some columns don't sort right
    //override them here
    $scope.getOrder = function() {
        switch($scope.predicate){
            case undefined:
            case '': 
            case 'Score': return sortScore;
            case 'Age': return sortAge;
            case 'Platform': return sortPlatform;
            case 'Ticket': return sortSev;
            default: return $scope.predicate;
        }
    }

    $scope.flashScreen = function() {
        var derp = document.body.style;
        derp.backgroundColor="yellow";
        setTimeout(function(){derp.backgroundColor="black";},100);
        setTimeout(function(){derp.backgroundColor="yellow";},150);
        setTimeout(function(){derp.backgroundColor="black";},175);
        setTimeout(function(){derp.backgroundColor="indigo";},250);
        setTimeout(function(){derp.backgroundColor="black";},300);
        setTimeout(function(){derp.backgroundColor="yellow";},325);
        setTimeout(function(){derp.backgroundColor="";},400);
    }
}
