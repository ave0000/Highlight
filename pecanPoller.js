var http = require("http");
var redis = require('redis');
require('console-ten').init(console);

var REDIS_URL = '/var/run/redis/redis.sock';
var PECAN_API = 'http://localhost/pecan-api/api/v2';

var db = redis.createClient(REDIS_URL);
db.on("error", function(err) {
  console.error("Error connecting to redis", err);
});
//db.select(1);

function saveWithStamp(db,name,data) {
	data.timestamp = Date.now();
	db.set(name+':timestamp',data.timestamp);
	var jdata = JSON.stringify(data);
	db.set(name,jdata);
	db.publish(name,jdata);
}

//npm install response
function jget(url,callback) {
	http.get(url,function(res){
		var data = [];
		if(res.statusCode !== 200) {
			console.log("Bad status: ", res.statusCode, url);
			return callback(data);
		}else{
			res.on('data',function(chunk){data.push(chunk);});
			res.on('end',function(){
				try{
					data = JSON.parse(data.join(''));
					data.url = url;
				}catch(e){
					console.log("Couldn't parse json from %s",url);
					data = ''
				}
				callback(data);
			});
		}
	}).on('error',function(e){
		console.log("Error: %s",e.message);
		callback([]);
	}).setTimeout(30000);
}

function saveSummary(db,profile,data) {
	var sum = data.summary;
	var summary = {
		profile: profile,
		totalCount: sum.total_count,
		latency: sum.latency
	}
	saveWithStamp(db,'summary:'+profile,summary);
}

function processQueue(data) {
	if(data && data.url && Array.isArray(data.tickets)) {
		var profile = 'ticketList:'+data.url.split('/').slice(-1)[0];
		var tickets =
			data.tickets.map(function(t) {return t['_id'];});
		saveWithStamp(db,profile,{tickets:tickets});
	}else
		console.log("Could not process data:",data);

	process.nextTick(popNext);
}

//Data is stale if timestamp was more than <limit:9000>ms ago
function isFresh(timeStamp,limit) {
	if(!timeStamp) return false;
	if(typeof(limit)==='undefined') limit = 9000;
	var diff = Date.now() - timeStamp;
	return diff < limit;
}

function newSummary(profile) {
	if(typeof(profile)==='undefined' || profile == "undefined") 
		return process.nextTick(popNext);

	db.get('ticketList:'+profile+':timestamp',function(err,reply){
		if(!isFresh(reply,30000)) {
			var url = PECAN_API + '/tickets/filter/' + profile;
			console.log("get: %s, %s",profile,url);
			jget(url,processQueue);
		}else{
			console.log("skip: %s",profile);
			process.nextTick(popNext);
			return false;
		}
	});
}

function popNext() {
	db.blpop('wantNewData', 0, function(err, data) {
		var profile = data[1].split(':')[1];
		newSummary(profile);
	});
}
popNext();
