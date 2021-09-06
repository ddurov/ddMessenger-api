<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Messages;
use Eviger\Api\Tools\Other;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    
    Other::checkToken($data['token']);

    switch ($data['method']) {

        case 'send':

            if (!isset($data['to_id'])) die(Other::generateJson(["response" => ["error" => "to_id not setted"]]));

            if (!isset($data['text'])) die(Other::generateJson(["response" => ["error" => "text not setted"]]));

            Messages::send($data['to_id'], $data['text'], $data['token']);

        break;
        default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {

    Other::checkToken($_GET['token']);
    
    switch ($_GET['method']) {

        case 'getDialogs':

            Messages::getDialogs($_GET['token']);

        break;
        case 'getHistory':

            if (!isset($_GET['id'])) die(Other::generateJson(["response" => ["error" => "id not setted"]]));

            Messages::getHistory($_GET['id'], $_GET['token']);

        break;
        default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}