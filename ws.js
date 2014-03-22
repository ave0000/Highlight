var redis = require('redis');
require('console-ten').init(console);

var WebSocketServer = require('ws').Server
  , wss = new WebSocketServer({port: 3000});

wss.on('connection', function(ws) {try{
    var clientHost = ws.upgradeReq.headers['x-forwarded-for'] || ws.upgradeReq.connection.remoteAddress;
	console.log(clientHost+' new connection');

	var db;
    db = redis.createClient('/dev/shm/redis.sock');
    db.on("error", function(err) {
      var msg = clientHost+"Error connecting to redis";
      console.error(msg, err);
      ws.close(1011,msg);
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
            case "rpush":
                db.rpush(parsed[1],parsed[2]);
                break;
            case "psubscribe":
                //subscribe blocks the redis connection
                //maybe create a new redis connect here?
                db.PSUBSCRIBE(parsed[1]);
                db.on("pmessage",function(pattern,channel,message){
                    try{
                        ws.send(message);
                    }catch(e) {
                        console.log('error sending pubished message to '+clientHost);
                        console.log(e);}
                })
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
            db.punsubscribe('*');
            db.end();
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
