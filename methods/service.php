<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";
require_once "tools/functions.php";

use Krugozor\Database\Mysql;

$db = Mysql::create("localhost", "user", "password")->setDatabaseName("eviger")->setCharset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    
    checkToken($data['token']);

    switch ($data['method']) {

    	default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]);
            
        break;

    }

} else {

	in_array($_GET['method'], ["getUpdates"]) ? true : checkToken($_GET['token']);
    
    switch ($_GET['method']) {
        
        case 'getUpdates':
            
            echo sendJson(
                ["response" => [
                    "version" => $db->query("SELECT version FROM eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['version'],
                    "download_link" => "https://".explode("/var/www/", $db->query("SELECT dl FROM eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['dl'])[1],
                    "changelog" => $db->query("SELECT changelog FROM eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['changelog']
                    ]
                ]
            );
            
        break;
        default:
            
            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]);
            
        break;
        
    }

}