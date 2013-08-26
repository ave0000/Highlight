<?php

//let's try to keep this simple...
function query($flags=''){
    $url = 'http://myhuntgroups.rackspace.com/vdns.json';

    //this might be a good place to try to cache
    //...

    $data = file_get_contents($url);
    return $data;
}

echo query();

?>

