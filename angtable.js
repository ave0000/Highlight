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
    }
);
app.filter('summaryColor',function(){
        return function(secs) {
            var red = 21600;
            var yellow = 10800;
            var color = 'green';
            if (secs >= red)
                color='red';
            else if (secs >= yellow)
                color='yellow';
            return color;
        }
    }
);


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
            if(data.profile)
                angular.extend($scope.summaries[data.profile],data)
            else
                console.log('fail: '+data);
            //$scope.loading = false;
            $scope.timeOutHolder = $timeout(function () {$scope.loadQueue(queue)}, $scope.refreshTime*1000);
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
            if(!data) data = [{"subject":"None"}];
            $scope.feedbacks = data;
            $scope.gettingFeedback = false;
            $scope.timeOutHolder = $timeout($scope.loadFeedback, $scope.refreshTime*1000);
        });

    };

    $scope.sortAge = function(a) {return parseInt(a.age_seconds);};
    $scope.sortScore = function(a) {return parseInt(a.score);};
}
