<?php

require_once "AoCucmAxl.php";

$axl = new AoCucmAxl('192.168.158.10','8443');

$results = $axl->executeSql('query','SELECT d.name,d.description FROM device d WHERE d.tkmodel = 437');

//print_r("Request:\n");
//var_dump($axl->_client->__getLastRequest());
//print_r("Response:\n");
var_dump($results);