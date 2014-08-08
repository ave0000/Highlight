var app = angular.module('personalQueue', ['Highlight']);

function Personal($scope, $http, $timeout) {
    var refreshTimeTimer;
    var lastLen;
    $scope.tickets = [];
    $scope.rev = false;
    $scope.refreshTime = 30;
    $scope.statusType = 4;

    $scope.columns =['Tickets','Age','Account','Subject','Status','OS'];

    $scope.$watch('showingTickets.length',function(len) {
        if(len !== undefined && len != lastLen) {
            lastLen = len;
            var str = window.parent.document.title;
            var repl = len==0 ? "$1" : "$1/"+len;
            var reg = (str.indexOf("/") > -1) ? /(^\d+)(\/\d+)/ : /^(\d+)/;
            str = str.replace(reg,repl)
            
            window.parent.document.title = str;
        }
    });

    //buffer modifications so we don't query on every keypress
    $scope.changeRefresh = function(n) {
        var refresh = n || 500;
        $timeout.cancel(refreshTimeTimer);
        refreshTimeTimer = $timeout(loadData, refresh);
    };

    var processTickets = function(data) {
        data.forEach(processTicket);
        return data;
    };
    var processTicket = function(t) {
        t.account = t["account.name"];
        t.account_id = t["account.number"];
        t.status = t["status.name"];
        t.sev = t["severity.name"];
        t.team = t["support_team.name"];
        t.age = t.idle;
        t.linux = t.has_linux_servers;
        t.windows = t.has_windows_servers;
        t.critical = t.has_critical_servers
        return t;
    };

    function loadData() {
        //don't start more requests if we're still pending
        if($scope.loading == true) return false;
        $scope.loading = true;
        var url = '/api/v1/mytickets?status_type='+$scope.statusType;

        $http.get(url)
        .success(function(data, status) {
            try{
                $scope.tickets = processTickets(data.response[0].result);
                $scope.error = null;
            }catch(e) {
                $scope.error = data.response.message;
                console.log('Did not receive ticket data');
            }
        }).error(function(data, status) {
            $scope.error = status + ' ' + url;
        }).finally(function(){
            $scope.loading = false;
            $scope.changeRefresh($scope.refreshTime * 1000);
            //$timeout.cancel($scope.timeOutHolder);
            //$scope.timeOutHolder = $timeout(loadData, $scope.refreshTime*1000);
        });
    };

    function sortPlatform(t) {
        var l = t.linux,w = t.windows;
        if(l && w) return 'both';
        if(l) return 'Linux';
        if(w) return 'Windows'};
    function sortSev(t) {
        if(t.sev == 'Emergency') return 9000;
        else if(t.sev == 'Urgent') return 1000;
        else return 0;
    };

    //some columns don't sort right
    //override them here
    $scope.getOrder = function() {
        switch($scope.sortBy){
            case undefined:
            case '': 
            case 'Age': return 'age';
            case 'Account': return 'account';
            case 'Subject': return 'subject';
            case 'Status': return 'status';
            case 'OS': return sortPlatform;
            case 'Ticket': return sortSev;
            default: return $scope.sortBy;
        }
    }
    $scope.changeSort = function(column) {
        if($scope.sortBy === column)
                $scope.rev = !$scope.rev;
        $scope.sortBy = column;
    }

    loadData();
}
