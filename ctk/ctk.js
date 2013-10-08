//surprised this isn't built in js functionality ...
function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

//var app = angular.module('myApp', []);
var app = angular.module('myApp', []);

app.filter('noSpaces',function(){
    return function(s){
        if(!s || !s.replace) return s;
        else return s.replace(/ /g,'');
    }
});

function Dynamic($scope, $http, $timeout) {
    $scope.tickets = [];
    $scope.refreshTime = 300;
    //i got a cookie!
    $scope.sso = readCookie('COOKIE_last_login');
    $scope.statusType = 4;
    $scope.title = "Tickets assigned to "+$scope.sso;

    $scope.changeRefresh = function() {
        //buffer modifications so we don't query on every keypress
        $timeout.cancel($scope.refreshTimeTimer);
        $scope.refreshTimeTimer = $timeout($scope.loadData, 1000);
    };

    $scope.loadData = function() {

        $scope.jsonQuery =       [
                {
                    "class": "Ticket.Ticket", 
                    "load_arg": {
                        "class": "Ticket.TicketWhere", 
                        "values": [
                            //[ "queue_name", "=", "Enterprise Services (All Teams)" ], 
                            //[ "queue_name", "=", "Ent - All" ], 
                            //"&",
                            [ "current_assignee_sso", "=", $scope.sso], 
                            "&",
                            [ "status_type", "=", $scope.statusType ]
                        ], 
                        //"limit": 5,
                        "offset": 0
                    }, 
                    "attributes": {
                        "number":"number", 
                        "account":"account.name",
                        "account_id":"account.number", //needed for link
                        "age":"age",
                        "status":"status.name",
                        //"statusColor":"status.color",
                        "subject":"subject",
                        "team":"support_team.name",
                        //"assignee":"assignee.name",
                        //"statuses":"all_status_flags",
                        "linux":"has_linux_servers",
                        "windows":"has_windows_servers",
                        "critical":"has_critical_servers"
                    }
                }
            ]

        //don't start more requests if we're still pending
        if($scope.loading == true) return false;
        $scope.loading = true;
//        $timeout.cancel($scope.timeOutHolder);
        //this would be a good place to sanity check
        var options = 'queue='+$scope.queueListSelect;
        var httpRequest = $http({
            method: 'POST',
            url: 'query.php',
            data: $scope.jsonQuery,

        }).success(function(data, status) {
            if(data && data[0] && data[0].result) {
                $scope.tickets = $scope.processTickets(data[0].result);
            }else{
                console.log('Did not receive ticket data');
                console.log(data);
            }
            $scope.loading = false;
            $scope.timeOutHolder = $timeout($scope.loadData, $scope.refreshTime*1000);
        }).error(function(data, status) {
            console.log(data);
            $scope.loading = false;
            $scope.timeOutHolder = $timeout($scope.loadData, $scope.refreshTime*1000);
        });
    };
    
    $scope.processTickets = function(data) {
            data.forEach($scope.processTicket);
            return data;
    };
    $scope.processTicket = function(t) {
        t.statuses = '';
        if(t.linux) t.statuses += 'L';
        if(t.windows) t.statuses += 'W';
        if(t.critical) t.statuses += 'C';
        return t;
    };
}
