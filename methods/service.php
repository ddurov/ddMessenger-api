<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Service;
use Eviger\Api\Tools\Other;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $tokenStatus = Other::checkToken($data['token']);

    $tokenStatus == true or die($tokenStatus);

    switch ($data['method']) {

    	default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {

    $tokenStatus = Other::checkToken($_GET['token']);

    if (!in_array($_GET['method'], ["getUpdates"])) $tokenStatus == true or die($tokenStatus);
    
    switch ($_GET['method']) {
        
        case 'getUpdates':
            die(Service::getUpdates());

        default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}
