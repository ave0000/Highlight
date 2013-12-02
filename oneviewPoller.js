var http = require("http");
var redis = require('redis');
require('console-ten').init(console);

var db = redis.createClient('/dev/shm/redis.sock');
db.on("error", function(err) {
  console.error(clientHost+"Error connecting to redis", err);
});

function saveTickets(db,profile,tickets) {
	var fields = [
		"OnWatch",
		"account",
		"aname",
		"fepochtime",
		"age_seconds",
		"category",
		"iscloud",
		"platform",
		"sev",
		"status",
		"subject",
		"team",
		"ticket",
		];
	var out = [];
	var len = tickets.length;
	var t, store, field;
	while(len--) {
		t = tickets[len];
		store = {};
		for(var i=0;i<fields.length;i++){
			field = fields[i];
			//only store good data
			if(t[field] && t[field] !== "null")
					store[field] = t[field];
		}
		db.hmset('ticket:'+t.ticket,store);
		out.push(t.ticket);
	}
	return out;
}

function saveTicketList(db,profile,tickets) {
	var name = 'ticketList:'+profile;
	saveWithStamp(db,name,tickets);
}

function saveSummary(db,profile,data) {
	var sum = data.summary;
	var num = 1;
	while(num<100 && sum['latency_'+num] === undefined)
		num++; //grep around in the dark
	var latstr = 'latency_'+num;

	var name = 'summary:'+profile+':'+latstr;
	var summary = {
		profile: profile,
		totalCount: sum.total_count,
		timeStamp: Date.now(),
		latency: sum[latstr]
	}
	saveWithStamp(db,name,summary);
}

function saveWithStamp(db,name,data) {
	var now = Date.now();
	data = JSON.stringify(data);
	db.set(name,data);
	db.set(name+':timestamp',now);
	db.publish(name,data);
}

function processQueue(data) {
	data = JSON.parse(data); // <-- need to wrap

	var profile = data.summary.profile_name;
	
	var multi = db.multi(); //lay in a course
	saveSummary(multi,profile,data);
	var ticketList = saveTickets(multi,profile,data.queue);
	saveTicketList(multi,profile,ticketList);
	
	//good place for a retry loop
	multi.exec(); // make it so
	//return console.log("Breakpoint");
	process.nextTick(popNext);
}

function fetchSummary(profile,latency) {
	var slick = 'http://oneview.rackspace.com/slick.php';
	var url = slick + '?fmt=json&latency='+latency+'&profile='+encodeURIComponent(profile);

	http.get(url,function(res){
		var data = '';
		res.on('data',function(chunk){data+=chunk;}); //may barf on unicode at chunk boundry?
		res.on('end',function(){processQueue(data);});
	}).on('error',function(e){
		console.log("Error: %s",e.message);
	}).setTimeout(30000);
}

//If timestamp was more than limit ago, then it's stale
function isFresh(timeStamp,limit) {
	if(!timeStamp) return false;
	if(typeof(limit)==='undefined') limit = 9000;
	var now = Date.now();
	var diff = now - timeStamp;
	return !(diff > limit); //that's confusing, why'd i write it that way?
}

function newSummary(d) {
	if(typeof(d)==='undefined') return process.nextTick(popNext);;
	var data = d.split(':');
	var profile = data[1];
	var latency = data[2];
	if(!profile || !latency) return process.nextTick(popNext);;
	db.get('ticketList:'+profile+':timestamp',function(err,reply){
		if(!isFresh(reply,30000)) {
			console.log("get: %s, %s",profile,latency);
			fetchSummary(profile,latency);
		}else{
			console.log("skip: %s",profile);
			process.nextTick(popNext);
		}		
	});
}

function popNext() {
	db.blpop('wantNewSummary', 0, function(err, data) {
		//console.log('processing: ' + data[1]);
		newSummary(data[1]);
	});
}

popNext();