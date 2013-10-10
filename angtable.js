"use strict";
var app = angular.module('myApp', []);

app.filter('timeCalc', function() {
    return function(secs) {
        if(secs == undefined) return "?";
        var minutes;
        var hours = parseInt(parseInt(secs) / 3600);

        secs -= (hours * 3600);
        minutes = parseInt(secs / 60);

        return hours + ':' + (minutes < 10 ? "0" : "") +  minutes;
    }
});

app.filter('summaryColor',function(){
    return function(secs) {
        var red = 21600;
        var yellow = 10800;
        var color = 'green';

        if(!secs || !(secs=parseInt(secs))) return color;
        if (secs >= red)
            color='red';
        else if (secs >= yellow)
            color='yellow';
        return color;
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
    //set a single preference value
    this.save = function(key,val){
        //maybe these should be buffered into blocks...
        //YES, they should be buffered.
      var prefs = {last: Date.now()};
      prefs[key] = val;
      $http({
            method: 'POST',
            url: prefurl+'?userPrefset',
            data: prefs,
        });
    }
    //return a single preference value
    this.get = function(key,$scope) {
        return $http.get(prefurl+'?userPrefs='+key)
            .then(function (response) {
                if(response.data && response.data[key])
                        $scope[key] = response.data[key];
            });
    };
    //load ALL preferences into the given scope
    this.load = function($scope) {
        $http.get(prefurl+'?userPrefs')
        .then(function(response){
            angular.extend($scope,response.data);
        });
    }
    //watch a variable in the given scope for changes
    this.watch = function(key,$scope) {
        $scope.$watch(key, function(newval, oldval) {
            //only want to change if USER changed value, not code
            //not sure how to fix
            if (newval!=undefined && newval !== oldval) {
                console.log('changing '+key+' to:"'+newval+'" from "'+oldval+'"');
                pref.save(key,newval);
            }
        });
    }
});

function Summary($scope, $http, $timeout,pref) {
    $scope.hideSummary = false;
    $scope.refreshTime = 60;
    $scope.summaries = {};

    pref.get('hideSummary',$scope);
    //$scope.hideSummary = 

    pref.watch('hideSummary',$scope);
    $scope.$watch('hideSummary', function(newval,oldval){
        if(newval == false && oldval== true)
            $scope.loadData();
    } );

    $scope.loadQueue = function(queue) {
        if($scope.hideSummary == true ) return false;
        var retryIn = $scope.refreshTime*1000;
        var httpRequest = $http({
            method: 'GET',
            url: 'summary.php?summary='+queue.profile+'&latency='+queue.latencyCount
        }).success(function(data, status) {
            if(data.profile)
                angular.extend($scope.summaries[data.profile],data);
            else if(data == '"try again soon"') retryIn = 1000;
            else{
                console.log('Summary fail: '+data);
                retryIn = 5000;
            }
            //console.log('next poll for '+queue.profile+' in '+retryIn);
            $timeout(function () {$scope.loadQueue(queue)}, retryIn);
        }).error(function(data,status) {
            //console.log(queue.profile+' httpfail: '+data+status);
            $timeout(function () {$scope.loadQueue(queue)}, retryIn);
        });
    };
    $scope.loadQueues = function(data){
        //pre-populate
        data.forEach(function(queue){
            $scope.summaries[queue.profile] = queue;
        });
        //fetch data
        data.forEach($scope.loadQueue);
    };
    //instead, we could have a list of user selected profiles to watch
    $scope.loadData = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'summary.php?summaryProfiles'
        }).success($scope.loadQueues);
    };

    if($scope.hideSummary == false) $scope.loadData();
}

function Dynamic($scope, $http, $timeout, pref) {
    $scope.reverse = true;
    $scope.predicate = 'Score';

    $scope.queueRefreshTime = 30;
    $scope.feedbacks = [];
    $scope.queueList = [];

    pref.load($scope);
    pref.watch('queueListSelect',$scope);
    //pref.watch('filterListSelect',$scope);
    pref.watch('filterSearch',$scope);
    pref.watch('queueRefreshTime',$scope);
    pref.watch('predicate',$scope);
    pref.watch('reverse',$scope);

    //refresh the queue when any of these are changed (blindly, but buffered)
    $scope.$watch('queueListSelect + queueRefreshTime + filterListSelect', 
        function(){$scope.changeRefresh();} );

    $scope.getQueueList = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?showProfiles',
            cache: true,
        }).success(function(data, status) {
            $scope.queueList = data;
        });
        $scope.queueList = '[{"Loading Options","Loading"}]';
    }
    $scope.getFilterList = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?showFilters'
            cache: true,
        }).success(function(data, status) {
            $scope.filterList = data;
        });
        $scope.filterList = '[Loading]';
    };

    $scope.changeRefresh = function() {
        //buffer modifications
        $timeout.cancel($scope.refreshTimeTimer);
        $scope.refreshTimeTimer = $timeout($scope.loadFeedback,1000);
    }

    $scope.processTickets = function(data) {
        data.forEach(function(t) {
        if(t.iscloud == "1") {
            var ticket = t.ticket.replace('ZEN_','');
            var account = t.account_link.replace('DDI ','');
            t.aname = t.account_link;

            t.ticketUrl='https://rackspacecloud.zendesk.com/tickets/'+ticket;
            t.accountUrl='https://rackspacecloud.zendesk.com/tickets/'+account;
        }else{
          t.ticketUrl='https://core.rackspace.com/ticket/'+t.ticket;
          t.accountUrl='https://core.rackspace.com/account/'+t.account;
        }
        });
        $scope.feedbacks = data;
    }

    $scope.loadFeedback = function() {
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

    $scope.sortAge = function(t) {return parseInt(t.age_seconds);};
    $scope.sortScore = function(t) {return (t.score=='-')?9999999:parseInt(t.score);};
    $scope.sortPlatform = function(t) {return t.platform;};

    //some columns don't sort right
    //override them here
    $scope.getOrder = function() {
        switch($scope.predicate){
            case undefined:
            case '': 
            case 'Score': return $scope.sortScore;
            case 'Age': return $scope.sortAge;
            case 'Platform': return $scope.sortPlatform;
            default: return $scope.predicate;
        }
    }

    $scope.flashScreen = function() {
        document.body.style.backgroundColor="yellow";
    }
}
