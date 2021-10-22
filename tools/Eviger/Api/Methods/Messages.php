<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Api\Tools\Other;
use Eviger\Database;

class Messages
{

    /**
     * @param string $toId
     * @param string $text
     * @param string $token
     * @return string
     * @throws selfThrows
     */
    public static function send(string $toId, string $text, string $token): string
    {

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($toId)) ? 0 : $toId, $toId);

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "to_id invalid"]);

        $toIdParsed = $selectAllOfUserObject->fetchAssoc()['id'];

        $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $peers = Database::getInstance()->query("SELECT peers FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $toIdParsed, $toIdParsed, $myId)->fetchAssoc()['peers'];

        $checkDialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $toIdParsed, $toIdParsed, $myId)->getNumRows();

        $time = time();

        if ($checkDialog) {

            $local_id_message = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers = '?s'", $peers)->fetchAssoc()['last_message_id'] + 1;

            Database::getInstance()->query("UPDATE eviger.eviger_dialogs SET last_message_sender = ?i, last_message_id = ?i, last_message_date = ?i, last_message = '?s' WHERE peers = '?s'", $myId, $local_id_message, $time, Other::encryptMessage($text), $peers);

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', ?i, ?i, ?i, '?s', ?i)", $peers, $local_id_message, $myId, $toIdParsed, Other::encryptMessage($text), $time);

            return (new Response)
                ->setStatus("ok")
                ->setResponse(["id" => (int)$local_id_message])
                ->toJson();

        }

        Database::getInstance()->query("INSERT INTO eviger.eviger_dialogs (peers, last_message_sender, last_message_id, last_message_date, last_message) VALUES ('?s', ?i, 1, ?i, '?s')", $myId . "," . $toIdParsed, $myId, $time, Other::encryptMessage($text));

        Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', 1, ?i, ?i, '?s', ?i)", $myId . "," . $toIdParsed, $myId, $toIdParsed, Other::encryptMessage($text), $time);

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["id" => 1])
            ->toJson();

    }

    /**
     * @param string $id
     * @param string $token
     * @return string
     * @throws selfThrows
     */
    public static function getHistory(string $id, string $token): string
    {

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($id)) ? 0 : $id, $id);

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "id invalid"]);

        $idParsed = $selectAllOfUserObject->fetchAssoc()['id'];

        $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $peers = Database::getInstance()->query("SELECT peers FROM eviger.eviger_dialogs WHERE peers REGEXP '(?i,?i|?i,?i)'", $myId, $idParsed, $idParsed, $myId)->fetchAssoc()['peers'];

        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_messages WHERE peers = '?s'", $peers);

        $dataArray = [];

        while ($data_parsed = $data->fetchAssoc()) {
            $dataArray[] = ["id" => (int)$data_parsed['local_id_message'], "out" => $data_parsed['out_id'] == Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'], "message" => Other::decryptMessage($data_parsed['message']), "date" => (int)$data_parsed['date']];
        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse([$dataArray])
            ->toJson();

    }

    /**
     * @param string $token
     * @return string
     */
    public static function getDialogs(string $token): string
    {

        $dataArray = [];
        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers LIKE '%?S%' ORDER BY last_message_date DESC", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

        while ($data_parsed = $data->fetchAssoc()) {
            $dataArray[] = ["last_message_id" => $data_parsed['last_message_id'], "last_message_sender" => $data_parsed['last_message_sender'], "creator_dialog_id" => (int)explode(",", $data_parsed['peers'])[0], "peer_id" => (int)explode(",", $data_parsed['peers'])[1], "date" => $data_parsed['last_message_date'], "message" => Other::decryptMessage($data_parsed['last_message'])];
        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse([$dataArray])
            ->toJson();

    }

}