"use strict";
var serverHost = window.location.hostname
var redisHost = serverHost+':3000';
var app = angular.module('Highlight', []);

//timceCalc runs a lot, needs to be efficient
//jsperfs indicate '+'' is good for concats
//some indicate that |0 is best for rounding
app.filter('timeCalc', function() {
    return function(secs) {
        if(secs % 1 !== 0) return '?';
        var mins = (secs/60|0)%60;
        return (secs/3600 |0) + (mins < 10 ? ':0' : ':') + mins;
    }
});

app.filter('timeSince', function(){
    return function(intime) {
        var secs = (Date.now() - intime)/1000|0;
        var mins = (secs/60|0)%60;
        return (secs/3600 |0) + (mins < 10 ? ':0' : ':') + mins;
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

app.filter('testColor',function(){
    return function(intime) {
        var secs = (Date.now() - intime);
        console.log(secs);
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

//requres score.js
app.filter('scoreCalc',function(){
    return ticketScore;
});
app.filter('trust', ['$sce', function($sce){//needed for ng-bind-html
        return function(text) {
            return $sce.trustAsHtml(text);
        };
}]);

//service to handle saving and loading preferences
app.service('pref',function($http){
    var pref = this;//so i can call myself
    var prefurl='jtable.php';
    this.cache = new Array();

    //set a single preference value
    this.save = function(key,val){
        if(pref.cache[key]!==undefined && pref.cache[key] === val) return true;
        console.log('saving '+key+' as:"'+val+'"');
        var prefs = {last: Date.now()};
        prefs[key] = val;
        //TODO: could buffer pref saves by
        //add to buffer and reset timeout
        $http({
            method: 'POST',
            url: prefurl+'?userPrefset',
            data: prefs,
        });
    }
    //watch a variable in the given scope for changes
    this.watch = function(key,$scope) {
        $http.get(prefurl+'?userPrefs='+key)
            .then(function (response) {
                if(response.data !==undefined && response.data[key] !== undefined) {
                    console.log("found pref: " + key + " = '" + response.data[key] + "'");
                    $scope[key] = pref.cache[key] = response.data[key];
                }
            });
        $scope.$watch(key, function(newval, oldval) {
            if (newval!==undefined && newval !== oldval)
                pref.save(key,newval);
        });
    }
});

//Directive to automatically persist a field's value.
app.directive('autoSave', function($timeout,$http) {
    var prefurl='jtable.php';
    var save = function(key,val){
        var prefs = {last: Date.now()};
        prefs[key] = val;
        console.log('Autosaving '+key+' as:"'+val+'"');
        $http.post(prefurl+"?userPrefset",prefs);
    }

    return {
        link: function($scope, $element, $attrs) {
            var savePromise;
            var key = $attrs.ngModel;

            $http.get(prefurl+'?userPrefs='+key)
                .then(function (response) {
                    if(response.data !==undefined && response.data[key] !== undefined) {
                        console.log("Autosave found pref: " + key + " = '" + response.data[key] + "'");
                        $scope[key] = response.data[key];
                    }
                });

            $scope.$watch(key, function(newval, oldval) {
                if (newval !== undefined && newval != oldval) {
                    $timeout.cancel(savePromise);
                    savePromise = $timeout(function() {save(key,newval);}, 750);
                }
            });
        }
    }
});


var requestSocket;
function Summary($scope, $http, $timeout,pref) {
    $scope.hideSummary = false;
    $scope.refreshTime = 30;
    $scope.summaries = {};
    $scope.profiles = [];

    //var requestSocket;
    function reqSocket(data) {//polling, more or less. ... ostensibly distributed...
        requestSocket = new WebSocket("ws://"+redisHost);
        requestSocket.onopen = function() {
            this.send(JSON.stringify(["PSUBSCRIBE", "summary:*"]));
            console.log("WebSocket connected and subscribed to summary updates.");
            data.forEach($scope.loadQueue);
        };
        requestSocket.onmessage = function(msg) {
            var data;
            try{data = JSON.parse(msg.data);}
            catch(e) {/*console.log("couldn't parse json");*/}
            if(data && data.profile)
                $scope.gotQueue(data);
        };
        requestSocket.onclose = function(a) {
            if($scope.hideSummary == true ) return true;
            console.log(a);
            setTimeout(function(){reqSocket(data)},4000);
        }   
    }
    
    $scope.gotQueue = function(queue) {
        var profile;
        if(!queue.profile || !$scope.summaries[queue.profile]) {
            var len=$scope.profiles.length;
            for (var i=0;i<len; i++) {
                if( $scope.profiles[i].filter == queue.profile ) {
                    profile = $scope.profiles[i];
                }
            }
            if(!profile) {
                console.log(queue);
                return false;
            }
        }else
            profile = $scope.summaries[queue.profile];

        angular.extend(profile,queue);

        //if we're not in a digest/apply cycle, start one
        if(!$scope.$$phase) $scope.$apply();
        //schedule the next poll
        $scope.loadQueue(queue);
    }
    $scope.loadQueue = function(queue) {
        if(requestSocket.readyState != WebSocket.OPEN) return false;
        var queueStr = 'summary:'+queue.profile;
        var age;
        var retryIn = $scope.refreshTime*1000;

        if(queue.timestamp===undefined) {//first run won't be populated
            requestSocket.send(JSON.stringify(["GET", queueStr]));
            queue.timestamp = 0;
            age = retryIn *0.75; //quarter of the normal refresh
        }else{
            age = Date.now() - queue.timestamp; //how long ago was it?
            if(age < 0) age = 0; //time travel ...
        }

        if(age >= retryIn) {
            //console.log(queue.profile+' data is '+age/1000+' seconds old, requesting new');
            requestSocket.send(JSON.stringify(["rpush","wantNewSummary",queueStr]));
        }else{
            var diff = retryIn - age; //reschedule
            //console.log(queue.profile+' is '+age+'ms old. next refresh in '+diff+'ms');
            $timeout.cancel(queue.timeout);
            queue.timeout = $timeout(function () {$scope.loadQueue(queue)}, diff);
        }
    };
    //fetch the list of profiles to render
    //instead, we could have a list of user selected profiles to watch
     function loadData() {
        var httpRequest = $http.get('profile_list.inc')
        .success(function(data){
            $scope.profiles = data;
            data.forEach(function(queue){//create cards
                $scope.summaries[queue.profile] = queue;
            });
            reqSocket(data);//fire up the socket engine
        });
    };

    $scope.$watch('hideSummary', function(newval,oldval){
        if(newval == false && oldval== true)
            loadData();
        else if(newval == true && requestSocket) {
            requestSocket.close();
        }
    });
    loadData();
}

function Flash($scope, $timeout) {
    $scope.toggle = true;
    $scope.flashScreen = function() {
        $scope.toggle = !$scope.toggle;
        var allTheThings = document.getElementsByClassName("ticketTable")[0].rows;

        var ahh = function(el){
            if(!el) return false;
            var e = el.style;
            e.backgroundColor = 'yellow';
            setTimeout(function(e){e.backgroundColor = '';},55,e);

            el = ($scope.toggle) ? el.nextElementSibling : el.previousElementSibling;
            setTimeout(ahh,40,el);
        }
        ahh(allTheThings[1]);
        
/*
        var derp = document.body.style;
        derp.backgroundColor="yellow";
        setTimeout(function(){derp.backgroundColor="black";},100);
        setTimeout(function(){derp.backgroundColor="yellow";},150);
        setTimeout(function(){derp.backgroundColor="indigo";},250);
        setTimeout(function(){derp.backgroundColor="black";},300);
        setTimeout(function(){derp.backgroundColor="yellow";},325);
        setTimeout(function(){derp.backgroundColor="";},400);
*/
    }
}
