<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Email;
use Eviger\Api\Tools\Other;
use Eviger\Mail;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($data['method']) {
        
        case 'createCode':
            if (!isset($data['email'])) die(Other::generateJson(["response" => ["error" => "email not setted"]]));

            die(Email::createCode($data['email'], Mail::getInstance()));

        case 'confirmCode':
            if (!isset($data['email'])) die(Other::generateJson(["response" => ["error" => "email not setted"]]));
    
            if (!isset($data['code'])) die(Other::generateJson(["response" => ["error" => "code not setted"]]));

            if (!isset($data['hash'])) die(Other::generateJson(["response" => ["error" => "hash not setted"]]));

            die(Email::confirmCode($data['email'], $data['code'], $data['hash']));

        default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {
    
    switch ($_GET['method']) {

    	default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}
