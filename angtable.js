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


function Summary($scope, $http, $timeout) {
    $scope.refreshTime = 60;
    $scope.summaries = {};

    $scope.loadQueue = function(queue) {
        //$timeout.cancel($scope.refreshTimeTimer);
        //$scope.loading=true;
        var httpRequest = $http({
            method: 'GET',
            url: 'summary.php?summary='+queue.profile+'&latency='+queue.latencyCount
        }).success(function(data, status) {
            var retryIn = $scope.refreshTime*1000;
            if(data.profile)
                angular.extend($scope.summaries[data.profile],data);
            else if(data == '"try again soon"') retryIn = 500;
            else{
                console.log('fail: '+data);
                retryIn = 500;
            }
            //$scope.loading = false;
            $scope.timeOutHolder = $timeout(function () {$scope.loadQueue(queue)}, retryIn);
        }).error(function(data,status) {console.log(queue.profile+' httpfail: '+data+status);});
    };
    $scope.loadQueues = function(data){
        //pre-populate
        data.forEach(function(queue){
            $scope.summaries[queue.profile] = queue;
        });
        //fetch data
        data.forEach($scope.loadQueue);
    };
    $scope.loadData = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'summary.php?summaryProfiles'
        }).success($scope.loadQueues);
    };
}

function Dynamic($scope, $http, $timeout) {
    $scope.predicate = 'score';
    $scope.reverse = true;
    $scope.queueList = [];
    $scope.ticketUrl = function(t) {
        if(t.iscloud == "1") {
            var ticket = t.ticket.replace('ZEN_','');
            return 'https://rackspacecloud.zendesk.com/tickets/'+ticket;
        }
        else return 'https://core.rackspace.com/ticket/'+t.ticket;
    }
    $scope.getQueueList = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?showProfiles'
        }).success(function(data, status) {
            $scope.queueList = data;
        });
        $scope.queueList = '[{"Loading Options","Loading"}]';
    }
    $scope.getFilterList = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?showFilters'
        }).success(function(data, status) {
            $scope.filterList = data;
        });
        $scope.filterList = '[Loading]';
    };

    $scope.feedbacks = [];
    $scope.refreshTime = 30;
    $scope.changeRefresh = function() {
        //buffer modifications
        $timeout.cancel($scope.refreshTimeTimer);
        $scope.refreshTimeTimer = $timeout($scope.loadFeedback,1000);
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
            var retryIn = $scope.refreshTime*1000;
            if(data == '"try again soon"') retryIn = 500;
            else if(!data || !(data instanceof Array))
                data = [{"subject":"None"}];
            else{
                $scope.feedbacks = data;
                $scope.gettingFeedback = false;
            }
            $scope.timeOutHolder = $timeout($scope.loadFeedback, retryIn);
        });
    };

    $scope.sortAge = function(t) {return parseInt(t.age_seconds);};
    $scope.sortScore = function(t) {return parseInt(t.score);};
    $scope.sortPlatform = function(t) {return t.platform;};
}
