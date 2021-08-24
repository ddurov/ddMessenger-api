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

	checkToken($_GET['token']);
    
    switch ($_GET['method']) {

    	case 'get':

    		if (!isset($_GET['id'])) {

			    echo sendJson(
			        ["response" => [
			            "eid" => (int)$db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'],
			            "username" => $db->query("SELECT username FROM eviger_users WHERE id = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'])->fetchAssoc()['username'],
			            "online" => (int)$db->query("SELECT online FROM eviger_users WHERE id = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'])->fetchAssoc()['online'],
			            "lastSeen" => (int)$db->query("SELECT lastSeen FROM eviger_users WHERE id = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'])->fetchAssoc()['lastSeen'],
			            "email" => $db->query("SELECT email FROM eviger_users WHERE id = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'])->fetchAssoc()['email']
			            ]
			        ]
		        );
			    
			} else {
			    
			    if (is_numeric($_GET['id'])) {
			    
			        if ($db->query("SELECT * FROM eviger_users WHERE id = ?i", $_GET['id'])->getNumRows()) {
			        
			            echo sendJson(
			                ["response" => [
			                    "eid" => (int)$db->query("SELECT id FROM eviger_users WHERE id = ?i", $_GET['id'])->fetchAssoc()['id'],
			                    "username" => $db->query("SELECT username FROM eviger_users WHERE id = ?i", $_GET['id'])->fetchAssoc()['username'],
			                    "online" => (int)$db->query("SELECT online FROM eviger_users WHERE id = ?i", $_GET['id'])->fetchAssoc()['online'],
			                    "lastSeen" => (int)$db->query("SELECT lastSeen FROM eviger_users WHERE id = ?i", $_GET['id'])->fetchAssoc()['lastSeen']
			                    ]
			                ]);
			                
			        } else {
			            
			            echo sendJson(["response" => ["error" => "user not found"]]);
			            
			        }
			        
			    } else {
			        
			        if ($db->query("SELECT * FROM eviger_users WHERE username = '?s'", $_GET['id'])->getNumRows()) {
			        
			            echo sendJson(
			                ["response" => [
			                    "eid" => (int)$db->query("SELECT id FROM eviger_users WHERE username = '?s'", $_GET['id'])->fetchAssoc()['id'],
			                    "username" => $db->query("SELECT username FROM eviger_users WHERE username = '?s'", $_GET['id'])->fetchAssoc()['username'],
			                    "online" => (int)$db->query("SELECT online FROM eviger_users WHERE username = '?s'", $_GET['id'])->fetchAssoc()['online'],
			                    "lastSeen" => (int)$db->query("SELECT lastSeen FROM eviger_users WHERE username = '?s'", $_GET['id'])->fetchAssoc()['lastSeen']
			                    ]
			                ]);
			                
			        } else {
			            
			            echo sendJson(["response" => ["error" => "user not found"]]);
			            
			        }
			        
			    }
			    
			}

    	break;
    	case 'search':

    		if (isset($_GET['query']) && $_GET['query'] !== "") {

			    $a = [];
			    $data = $db->query("SELECT * FROM eviger_users WHERE username LIKE '%?S%'", $_GET['query']);
			
			    while ($data_parsed = $data->fetchAssoc()) {
			        
			        $a[] = ["eid" => (int)$data_parsed['id'], "username" => $data_parsed['username'], "online" => (int)$data_parsed['online'], "lastSeen" => (int)$data_parsed['lastSeen']];
			        
			    }
			
			    echo sendJson(["response" => $a]);
			
			} else {
			
			    echo sendJson(["response" => []]);
			
			}

    	break;
    	default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]);

        break;

    }

}
