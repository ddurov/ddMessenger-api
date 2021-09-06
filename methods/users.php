<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Users;
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

    $tokenStatus == true or die($tokenStatus);
    
    switch ($_GET['method']) {

    	case 'get':
    		if (!isset($_GET['id'])) die(Users::get($_GET['token']));

            die(Users::get($_GET['token']));

        case 'search':
    		if (isset($_GET['query']) && $_GET['query'] !== "") {

                die(Users::search($_GET['query']));
			
			} else {
			
			    die(Other::generateJson(["response" => []]));
			
			}

        default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}
