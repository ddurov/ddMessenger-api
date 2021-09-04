<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;
use Krugozor\Database\MySqlException;

class Messages
{
    /**
     * @throws MySqlException
     */
    public function send(string $toId, string $text, string $token) {

        if (is_numeric($toId)) {

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE id = ?i", (int)$toId)->getNumRows()) {

                $to_id = $toId;

            } else {

                die((new Other)->generateJson(["response" => ["error" => "to_id incorrect"]]));

            }

        } else {

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $toId)->getNumRows()) {

                $to_id = Database::getInstance()->query("SELECT id FROM eviger.eviger_users WHERE username = '?s'", $toId)->fetchAssoc()['id'];

            } else {

                die((new Other)->generateJson(["response" => ["error" => "to_id incorrect"]]));

            }

        }

        $time = time();

        $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $peers = Database::getInstance()->query("SELECT peers FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $to_id, $to_id, $myId)->fetchAssoc()['peers'];

        $checkDialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $to_id, $to_id, $myId)->getNumRows();

        if ($checkDialog) {

            $local_id_message = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers = '?s'", $peers)->fetchAssoc()['last_message_id'] + 1;

            Database::getInstance()->query("UPDATE eviger.eviger_dialogs SET last_message_sender = ?i, last_message_id = ?i, last_message_date = ?i, last_message = '?s' WHERE peers = '?s'", $myId, $local_id_message, $time, (new Other())->encryptMessage($text), $peers);

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', ?i, ?i, ?i, '?s', ?i)", $peers, $local_id_message, $myId, $to_id, (new Other())->encryptMessage($text), $time);

            die((new Other)->generateJson(["response" => ["id" => (int)$local_id_message]]));

        } else {

            Database::getInstance()->query("INSERT INTO eviger.eviger_dialogs (peers, last_message_sender, last_message_id, last_message_date, last_message) VALUES ('?s', ?i, 1, ?i, '?s')", $myId.",".$to_id, $myId, $time, (new Other())->encryptMessage($text));

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', 1, ?i, ?i, '?s', ?i)", $myId.",".$to_id, $myId, $to_id, (new Other())->encryptMessage($text), $time);

            die((new Other)->generateJson(["response" => ["id" => 1]]));

        }

    }
    public function getHistory($id, $token){

        if (is_numeric($id)) {

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE id = ?i", $id)->getNumRows()) {

                $id_getData = $id;

            } else {

                die((new Other)->generateJson(["response" => ["error" => "id incorrect"]]));

            }

        } else {

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $id)->getNumRows()) {

                $id_getData = Database::getInstance()->query("SELECT id FROM eviger.eviger_users WHERE username = '?s'", $id)->fetchAssoc()['id'];

            } else {

                die((new Other)->generateJson(["response" => ["error" => "id incorrect"]]));

            }

        }

        $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $peers = Database::getInstance()->query("SELECT peers FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $id_getData, $id_getData, $myId)->fetchAssoc()['peers'];

        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_messages WHERE peers = '?s'", $peers);

        $dataArray = [];

        while ($data_parsed = $data->fetchAssoc()) {

            $dataArray[] = ["id" => (int)$data_parsed['local_id_message'], "out" => $data_parsed['out_id'] == Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'], "message" => (new Other)->decryptMessage($data_parsed['message']), "date" => (int)$data_parsed['date']];

        }

        die((new Other)->generateJson(["response" => $dataArray]));

    }
    public function getDialogs(string $token) {

        $dataArray = [];
        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers LIKE '%?S%' ORDER BY last_message_date DESC", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

        while ($data_parsed = $data->fetchAssoc()) {

            $dataArray[] = ["last_message_id" => $data_parsed['last_message_id'], "last_message_sender" => $data_parsed['last_message_sender'], "creator_dialog_id" => (int)explode(",", $data_parsed['peers'])[0], "peer_id" => (int)explode(",", $data_parsed['peers'])[1], "date" => $data_parsed['last_message_date'], "message" => (new Other)->decryptMessage($data_parsed['last_message'])];

        }

        die((new Other)->generateJson(["response" => $dataArray]));

    }
}