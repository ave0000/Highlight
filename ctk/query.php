<?php

//Proxying the CTK request to work around origin policy
function query($token,$query){
    //this might be a good place to try to cache
    //...
    $ctkurl = 'https://ws.core.rackspace.com/ctkapi';
    #$postData = json_encode($query);
    $postData = $query;
    $params = '/query';


    //This is necessary for the post data
    //ignore_errors is set so it passes the error instead of exploding
    $opts = array('http' => 
        array(
            'method' => 'POST',
            'header' => array(
                'Content-type: application/json',
                "X-Auth: $token"
            ),
            'content' => $postData,
            'ignore_errors' => true,
        )
    );
    $context = stream_context_create($opts);

    $data = file_get_contents($ctkurl.$params,false,$context);
    return $data;
}

function getLastComment($token,$query){
    //this might be a good place to try to cache
    //...
    $ctkurl = 'https://proton.rackspace.com/webServices/getLastComment.php';

    //This is necessary for the post data
    //ignore_errors is set so it passes the error instead of exploding
    $opts = array('http' => 
        array(
            'method' => 'POST',
            'header' => array(
                //'Cookie: rackspace_admin_session=ca799521f3244c323e71c894bee7bf8d;',
                'Cookie: rackspace_admin_session='.$token.';',
            ),
            'content' => "ticketNo=$query",
            'ignore_errors' => true,
        )
    );
    $context = stream_context_create($opts);

    $data = file_get_contents($ctkurl,false,$context);
    return $data;
}

//ask CTK to whom this token belongs
function getUserName($token){
    $ctkurl = 'https://ws.core.rackspace.com/ctkapi';
    $params = '/session/'.$token;

    $data = file_get_contents($ctkurl.$params);
    $data = json_decode($data);
    return $data->username;
}

if(isset($_COOKIE['rackspace_admin_session']))
    $token = $_COOKIE['rackspace_admin_session'];
if(isset($_REQUEST['token'])) {
    $token = $_REQUEST['token'];
}

if(!isset($token)) {
    echo 'No token provided.  Please login first.';
    exit();
}

if(isset($_REQUEST['getUser'])){
    echo getUserName($token);
}else if(isset($_REQUEST['ticket'])){
    echo getLastComment($token,$_REQUEST['ticket']);
}else if(isset($_POST)){
    //this is apparently how you read POST:
    $postdata = file_get_contents("php://input");

    //probably should check token first
    echo query($token,$postdata);
}

?>

