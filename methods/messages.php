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

        case 'send':

            if (isset($data['to_id'])) {

                if (isset($data['text'])) {

                    if (is_numeric($data['to_id'])) {

                        if ($db->query("SELECT * FROM eviger_users WHERE id = ?i", (int)$data['to_id'])->getNumRows()) {

                            $to_id = $data['to_id'];

                        } else {

                            die(sendJson(["response" => ["error" => "to_id incorrect"]]));

                        }

                    } else {

                        if ($db->query("SELECT * FROM eviger_users WHERE username = '?s'", $data['to_id'])->getNumRows()) {

                            $to_id = $db->query("SELECT id FROM eviger_users WHERE username = '?s'", $data['to_id'])->fetchAssoc()['id'];

                        } else {

                            die(sendJson(["response" => ["error" => "to_id incorrect"]]));

                        }

                    }

                    $time = time();

                    $myId = $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $data['token'])->fetchAssoc()['eid'];

                    $peers = $db->query("SELECT peers FROM eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $to_id, $to_id, $myId)->fetchAssoc()['peers'];

                    $checkDialog = $db->query("SELECT * FROM eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $to_id, $to_id, $myId)->getNumRows();

                    if ($checkDialog) {

                        $local_id_message = $db->query("SELECT * FROM eviger_dialogs WHERE peers = '?s'", $peers)->fetchAssoc()['last_message_id'] + 1;

                        $db->query("UPDATE eviger_dialogs SET last_message_sender = ?i, last_message_id = ?i, last_message_date = ?i, last_message = '?s' WHERE peers = '?s'", $myId, $local_id_message, $time, encryptMessage($data['text']), $peers);

                        $db->query("INSERT INTO eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', ?i, ?i, ?i, '?s', ?i)", $peers, $local_id_message, $myId, $to_id, encryptMessage($data['text']), $time);

                        echo sendJson(["response" => ["id" => (int)$local_id_message]]);

                    } else {

                        $db->query("INSERT INTO eviger_dialogs (peers, last_message_sender, last_message_id, last_message_date, last_message) VALUES ('?s', ?i, 1, ?i, '?s')", $myId.",".$to_id, $myId, $time, encryptMessage($data['text']));

                        $db->query("INSERT INTO eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', 1, ?i, ?i, '?s', ?i)", $myId.",".$to_id, $myId, $to_id, encryptMessage($data['text']), $time);

                        echo sendJson(["response" => ["id" => (int)1]]);

                    }

                } else {

                    echo sendJson(["response" => ["error" => "text not setted"]]);

                }

            } else {

                echo sendJson(["response" => ["error" => "to_id not setted"]]);

            }

        break;
        default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]);

        break;
        
    }

} else {

    checkToken($_GET['token']);
    
    switch ($_GET['method']) {

        case 'getDialogs':

            $a = [];
            $data = $db->query("SELECT * FROM eviger_dialogs WHERE peers LIKE '%?S%' ORDER BY last_message_date DESC", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid']);

            while ($data_parsed = $data->fetchAssoc()) {

                $a[] = ["last_message_id" => $data_parsed['last_message_id'], "last_message_sender" => $data_parsed['last_message_sender'], "creator_dialog_id" => (int)explode(",", $data_parsed['peers'])[0], "peer_id" => (int)explode(",", $data_parsed['peers'])[1], "date" => $data_parsed['last_message_date'], "message" => decryptMessage($data_parsed['last_message'])];

            }

            echo sendJson(["response" => $a]);

        break;
        case 'getHistory':

            if (isset($_GET['id'])) {

                if (is_numeric($_GET['id'])) {

                    if ($db->query("SELECT * FROM eviger_users WHERE id = ?i", (int)$_GET['id'])->getNumRows()) {

                        $id_getData = $_GET['id'];

                    } else {

                        die(sendJson(["response" => ["error" => "id incorrect"]]));

                    }

                } else {

                    if ($db->query("SELECT * FROM eviger_users WHERE username = '?s'", $_GET['id'])->getNumRows()) {

                        $id_getData = $db->query("SELECT id FROM eviger_users WHERE username = '?s'", $_GET['id'])->fetchAssoc()['id'];

                    } else {

                        die(sendJson(["response" => ["error" => "id incorrect"]]));

                    }

                }

                $myId = $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'];

                $peers = $db->query("SELECT peers FROM eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $id_getData, $id_getData, $myId)->fetchAssoc()['peers'];

                $data = $db->query("SELECT * FROM eviger_messages WHERE peers = '?s'", $peers);

                $a = [];

                while ($data_parsed = $data->fetchAssoc()) {

                    $a[] = ["id" => (int)$data_parsed['local_id_message'], "out" => $data_parsed['out_id'] == $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'], "message" => decryptMessage($data_parsed['message']), "date" => (int)$data_parsed['date']];

                }

                echo sendJson(["response" => $a]);

            } else {

                echo sendJson(["response" => ["error" => "id not setted"]]);

            }

        break;
        default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]);
            
        break;

    }

}