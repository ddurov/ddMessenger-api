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
            
            if (isset($data['email'])) {

                Email::createCode($data['email'], Mail::getInstance());
                
            } else {

                die(Other::generateJson(["response" => ["error" => "email not setted"]]));
                
            }

        break;
        case 'confirmCode':

            if (isset($data['email'])) {
    
                if (isset($data['code'])) {

                    Email::confirmCode($data['email'], $data['code'], $data['hash']);
                    
                } else {

                    die(Other::generateJson(["response" => ["error" => "code not setted"]]));
                    
                }
                
            } else {

                die(Other::generateJson(["response" => ["error" => "email not setted"]]));
                
            }

        break;
    	default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {
    
    switch ($_GET['method']) {

    	default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}
