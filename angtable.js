"use strict";

var app = angular.module('myApp', []);


app.filter('timeCalc', function() {
        return function(secs) {
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

function PeopleCtrl($scope, $http) {
    $scope.people = [];

    $scope.loadPeople = function() {
        var httpRequest = $http({
            method: 'GET',
            url: 'echo.php',
            //data: 'datas'

        }).success(function(data, status) {
            $scope.people = data;
        });

    };

}

function Summary($scope, $http, $timeout) {
    $scope.refreshTime = 120;
    $scope.loadData = function() {
        $timeout.cancel($scope.refreshTimeTimer);
        $scope.loading=true;
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?summary=get',
            //data: 'datas'

        }).success(function(data, status) {
            $scope.timeStamp = data.timeStamp;
            $scope.summaries = data.summaries;
            $scope.loading = false;
            $scope.timeOutHolder = $timeout($scope.loadData, $scope.refreshTime*1000);
        });

    };
}

function Dynamic($scope, $http, $timeout) {
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
        //var options = 'queue='+$scope.queueListSelect;
        var options = 'queue';
        if($scope.queueListSelect != undefined)
            options = options+'='+$scope.queueListSelect;
        if($scope.filterListSelect != undefined) {
            options = options+'&filter='+$scope.filterListSelect;
        }
        $scope.gettingFeedback = true;
        var httpRequest = $http({
            method: 'GET',
            url: 'jtable.php?'+options,
        }).success(function(data, status) {
            $scope.feedbacks = data;
            $scope.gettingFeedback = false;
            $scope.timeOutHolder = $timeout($scope.loadFeedback, $scope.refreshTime*1000);
        });

    };
}
