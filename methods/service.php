<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Service;
use Eviger\Api\Tools\Other;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    
    Other::checkToken($data['token']);

    switch ($data['method']) {

    	default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {

	in_array($_GET['method'], ["getUpdates"]) ? true : Other::checkToken($_GET['token']);
    
    switch ($_GET['method']) {
        
        case 'getUpdates':
            
            Service::getUpdates();
            
        break;
        default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}