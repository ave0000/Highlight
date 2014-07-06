var redis = require('redis')
  , http = require('http')
  , express = require('express')
  , bodyParser = require('body-parser')
  , httpProxy = require('http-proxy')
  , app = express();
  require('console-ten').init(console);


var REDIS_URL = '/var/run/redis/redis.sock';


var prefs = express.Router()
    .use(function(req,res,next) {
        res.db = redis.createClient(REDIS_URL);
        res.key = 'prefs:';
        if(req.headers.cookie) {
            cookies = {};
            req.headers.cookie.split('; ').forEach(function(c){
                var C = c.split('=');
                cookies[C[0]] = C[1];
            })
        }
        if(cookies.COOKIE_last_login){
            res.key += cookies.COOKIE_last_login;
        }else if(req.query.user){
            res.key += req.query.user
        }else{
            //console.log("no USER!!!");
        }
        next();
    })
    .get('/',function(req,res,next){
        if(req.query.userPrefs)
            res.pref = req.query.userPrefs;
        next();
    })
    .all('/:pref',function(req,res,next){
        if(req.params.pref)
            res.pref = req.params.pref;
        next();
    })
    .post('/:pref',function(req,res,next) {
        if(req.params.pref) {
            res.db.hset(key,req.params.pref,req.body)
            res.db.hset(key,'last',Date.now())
        }
        next();
    })
    .use(bodyParser.json())
    .post('/',function(req,res,next) {
            if(req.query.userPrefset !== undefined)
                res.db.hmset(res.key,req.body)
                next();
            })
    .use(function(req,res,next){
        var callback = function(err,reply) {
            var out = {};
            if(reply) out[res.pref] = reply;
            res.send(err || out);
        }
        if(res.pref)
            res.db.hget(res.key,res.pref,callback);
        else
            res.db.hgetall(res.key,callback);
    });


var apiProxy = httpProxy.createProxyServer();


app.use('/jtable.php',prefs);
app.use('/prefs/',prefs);
app.use('/api',function(req, res){ 
    req.url = '/api/'+req.url;
  apiProxy.web(req, res, { target: 'http://localhost:80' });
})
app.use(express.static(__dirname));

var server = http.createServer(app);
server.listen(process.env.PORT || 3000);

var WebSocketServer = require('ws').Server
  , wss = new WebSocketServer({server: server});

wss.on('connection', function(ws) {try{
    var clientHost = ws.upgradeReq.headers['x-forwarded-for'] || ws.upgradeReq.connection.remoteAddress;
	console.log(clientHost+' new connection');

	var db;
    var pubdb;
    db = redis.createClient(REDIS_URL);
    db.on("error", function(err) {
      var msg = clientHost+"Error connecting to redis";
      console.error(msg, err);
      ws.close(msg);
    });
    ws.on('message', function(message) {
        var parsed;
        try {parsed = JSON.parse(message);}
        catch(e) {ws.send("Error: I didn't understand that json");}

        if(parsed && parsed[0] && parsed[0].toLowerCase) {
        switch(parsed[0].toLowerCase()) {
            case "get":
                db.get(parsed[1],function(err,reply) {if(reply) ws.send(reply);});
                break;
            case "prefs":
                db.hgetall('prefs:'+parsed[1],function(err,reply) {if(reply) ws.send(reply);})
                break;
            case "rpush":
                db.rpush(parsed[1],parsed[2]);
                break;
            case "psubscribe":
                //subscribe blocks the redis connection
                if(pubdb && pubdb.connected) pubdb.quit();
                pubdb = redis.createClient(REDIS_URL);
                pubdb.PSUBSCRIBE(parsed[1]);
                pubdb.on("pmessage",function(pattern,channel,message){
                    try{
                        ws.send(message);
                    }catch(e) {
                        console.log('error sending pubished message to '+clientHost);
                        console.log(e);
                        pubdb.quit();
                    }
                });
                break;
            case "selectprofile":
                //send any data that's available
                db.get(parsed[1],function(err,reply) {if(reply) ws.send(reply);});
                //subscribe to updates
                if(pubdb && pubdb.connected) pubdb.quit();
                pubdb = redis.createClient(REDIS_URL);
                pubdb.PSUBSCRIBE(parsed[1]);
                pubdb.on("pmessage",function(pattern,channel,message){
                    try{
                        ws.send(message);
                    }catch(e) {
                        console.log('error sending pubished message to '+clientHost);
                        console.log(e);
                        pubdb.quit();
                    }
                });
                //request a refresh when a global event occurs
                pubdb.subscribe("newData");
                pubdb.on("message",function(channel,message){
                    if(message == "core" || message == "cloud")
                        db.rpush("wantNewData",parsed[1]);
                });
                break;
            case "bye":
            case "close":
                ws.close(1000,"Thank you, come again!");
                break;
            case "stats":
                stats(ws,wss,db);
                break;
            default:
                console.log('received: %s', message);
        }}else console.log("Didn't understand %s", message);
    });
    ws.on('close',function(){
        console.log(clientHost+' closed connection');
        if(db && db.connected) {
            db.end();
        }
        if(pubdb && pubdb.connected) {
            pubdb.end();
        }
    });
}catch(e) {console.log(e);}
});

function stats(ws,wss,db) {
    var clientHosts = []
    console.log('client requested stats');
    for (var i in wss.clients) {
        var c = wss.clients[i];
        clientHosts.push(c.upgradeReq.headers['x-forwarded-for'] || c.upgradeReq.connection.remoteAddress);
        
    }
    var data = { clients: clientHosts, count: clientHosts.length };
    ws.send(JSON.stringify(data));
    //ws.send(JSON.stringify(ws,replacer,2));
    //ws.send('=== Server Level ===');
    //ws.send(JSON.stringify(wss.clients,replacer,2));
}