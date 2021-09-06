<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;
use Exception;

class Messages
{

    /**
     * @param string $toId
     * @param string $text
     * @param string $token
     * @return string
     */
    public static function send(string $toId, string $text, string $token): string {

        try {

            if (is_numeric($toId)) {

                if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE id = ?i", (int)$toId)->getNumRows()) return Other::generateJson(["response" => ["error" => "to_id incorrect"]]);

                $to_id = $toId;

            } else {

                if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $toId)->getNumRows()) return Other::generateJson(["response" => ["error" => "to_id incorrect"]]);

                $to_id = Database::getInstance()->query("SELECT id FROM eviger.eviger_users WHERE username = '?s'", $toId)->fetchAssoc()['id'];

            }

            $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

            $peers = Database::getInstance()->query("SELECT peers FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $to_id, $to_id, $myId)->fetchAssoc()['peers'];

            $checkDialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $to_id, $to_id, $myId)->getNumRows();

            $time = time();

            if ($checkDialog) {

                $local_id_message = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers = '?s'", $peers)->fetchAssoc()['last_message_id'] + 1;

                Database::getInstance()->query("UPDATE eviger.eviger_dialogs SET last_message_sender = ?i, last_message_id = ?i, last_message_date = ?i, last_message = '?s' WHERE peers = '?s'", $myId, $local_id_message, $time, Other::encryptMessage($text), $peers);

                Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', ?i, ?i, ?i, '?s', ?i)", $peers, $local_id_message, $myId, $to_id, Other::encryptMessage($text), $time);

                return Other::generateJson(["response" => ["id" => (int)$local_id_message]]);

            } else {

                Database::getInstance()->query("INSERT INTO eviger.eviger_dialogs (peers, last_message_sender, last_message_id, last_message_date, last_message) VALUES ('?s', ?i, 1, ?i, '?s')", $myId.",".$to_id, $myId, $time, Other::encryptMessage($text));

                Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', 1, ?i, ?i, '?s', ?i)", $myId.",".$to_id, $myId, $to_id, Other::encryptMessage($text), $time);

                return Other::generateJson(["response" => ["id" => 1]]);

            }

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

    /**
     * @param $id
     * @param $token
     * @return string
     */
    public static function getHistory($id, $token): string {

        try {

            if (is_numeric($id)) {

                if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE id = ?i", $id)->getNumRows()) return Other::generateJson(["response" => ["error" => "id incorrect"]]);

                $idGetHistory = $id;

            } else {

                if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $id)->getNumRows()) return Other::generateJson(["response" => ["error" => "id incorrect"]]);

                $idGetHistory = Database::getInstance()->query("SELECT id FROM eviger.eviger_users WHERE username = '?s'", $id)->fetchAssoc()['id'];

            }

            $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

            $peers = Database::getInstance()->query("SELECT peers FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $idGetHistory, $idGetHistory, $myId)->fetchAssoc()['peers'];

            $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_messages WHERE peers = '?s'", $peers);

            $dataArray = [];

            while ($data_parsed = $data->fetchAssoc()) {

                $dataArray[] = ["id" => (int)$data_parsed['local_id_message'], "out" => $data_parsed['out_id'] == Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'], "message" => Other::decryptMessage($data_parsed['message']), "date" => (int)$data_parsed['date']];

            }

            return Other::generateJson(["response" => $dataArray]);

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

    /**
     * @param string $token
     * @return string
     */
    public static function getDialogs(string $token): string {

        try {

            $dataArray = [];
            $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers LIKE '%?S%' ORDER BY last_message_date DESC", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

            while ($data_parsed = $data->fetchAssoc()) {

                $dataArray[] = ["last_message_id" => $data_parsed['last_message_id'], "last_message_sender" => $data_parsed['last_message_sender'], "creator_dialog_id" => (int)explode(",", $data_parsed['peers'])[0], "peer_id" => (int)explode(",", $data_parsed['peers'])[1], "date" => $data_parsed['last_message_date'], "message" => Other::decryptMessage($data_parsed['last_message'])];

            }

            return Other::generateJson(["response" => $dataArray]);

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

}