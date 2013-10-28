var redis = require('redis');
require('console-ten').init(console);

var WebSocketServer = require('ws').Server
  , wss = new WebSocketServer({port: 3000});

wss.on('connection', function(ws) {try{
    var clientHost = ws.upgradeReq.headers['x-forwarded-for'] || ws.upgradeReq.connection.remoteAddress;
	console.log(' '+clientHost+' new connection');

	var db = redis.createClient('/dev/shm/redis.sock');
    ws.on('message', function(message) {
        var parsed;
        try {parsed = JSON.parse(message);}
        catch(e) {ws.send("Error: I didn't understand that json");}

        if(parsed) {
        switch(parsed[0]) {
            case "GET":
                db.get(parsed[1],function(err,reply) {ws.send(reply);});
                break;
            case "PSUBSCRIBE":
                //subscribe blocks the redis connection
                //probably should create a new redis connect here
                //
                db.PSUBSCRIBE(parsed[1]);
                db.on("pmessage",function(pattern,channel,message){
                    try{
                        ws.send(message);
                    }catch(e) {
                        console.log('error sending pubished message to '+clientHost);
                        console.log(e);}
                })
                ws.on('close',function(){
                    console.log(clientHost+'closed connection');
                    db.unsubscribe();
                    db.end();
                });
                break;
            case "rpush":
                //console.log(parsed);
                db.rpush(parsed[1],parsed[2]);
                break;
            case "stats":
                console.log(clientHost+' requested stats');
                ws.send('Dumping stats');
                ws.send();
                ws.send('=== Server Level ===');
                ws.send(JSON.stringify(wss.clients));
                console.log(wss.clients);
                break;
            default:
                console.log('received: %s', message);
        }}
    });
    //ws.send('wordl');
}catch(e) {console.log(e);}
});

