<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Api\Tools\Other;
use Eviger\Database;
use Krugozor\Database\MySqlException;

class Messages
{

    /**
     * @param int $toId
     * @param string $text
     * @param string $token
     * @return string
     * @throws selfThrows|MySqlException
     */
    public static function send($toId, string $text, string $token): string
    {

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($toId)) ? 0 : $toId, $toId);

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "to_id invalid"]);

        $idParsed = (int)$selectAllOfUserObject->fetchAssoc()['id'];

        $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $dialogData = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers LIKE '%?S%'", $myId);

        $foundedPeers = ($idParsed === $myId) ? "$myId,$myId" : null;

        while ($dialogParsed = $dialogData->fetchAssoc()) {
            preg_match_all("/[^$myId,]+/", $dialogParsed['peers'], $matches);
            if (isset($matches[0][0])) {
                $foundedPeers = $dialogParsed['peers'];
                break;
            }
            $foundedPeers = null;
        }

        $time = time();

        if ($foundedPeers !== null) {

            $local_id_message = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers = '?s'", $foundedPeers)->fetchAssoc()['last_message_id'] + 1;

            Database::getInstance()->query("UPDATE eviger.eviger_dialogs SET last_message_sender = ?i, last_message_id = ?i, last_message_date = ?i, last_message = '?s' WHERE peers = '?s'", $myId, $local_id_message, $time, Other::encryptMessage($text), $foundedPeers);

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', ?i, ?i, ?i, '?s', ?i)", $foundedPeers, $local_id_message, $myId, $idParsed, Other::encryptMessage($text), $time);

            $personalIdLongPollData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE eid = ?i", $myId)->getNumRows();

            Database::getInstance()->query("INSERT INTO eviger.eviger_longpoll_data (personalIdEvent, eid, type, dataSerialized) VALUES (?i, ?i, 1, '?s')", $personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1, $myId, serialize(["eventId" => (int)($personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1), "eventType" => "newMessage", "objects" => ["id" => (int)$local_id_message, "out_id" => (int)$myId, "peer_id" => (int)$idParsed, "message" => Other::encryptMessage($text), "date" => $time]]));

            return (new Response)
                ->setStatus("ok")
                ->setResponse(["id" => (int)$local_id_message])
                ->toJson();

        }

        Database::getInstance()->query("INSERT INTO eviger.eviger_dialogs (peers, last_message_id, last_message_sender, last_message, last_message_date) VALUES ('?s', 1, ?i, '?s', ?i)", "$myId,$idParsed", $myId, Other::encryptMessage($text), $time);

        Database::getInstance()->query("INSERT INTO eviger.eviger_messages (peers, local_id_message, out_id, peer_id, message, date) VALUES ('?s', 1, ?i, ?i, '?s', ?i)", "$myId,$idParsed", $myId, $idParsed, Other::encryptMessage($text), $time);

        $personalIdLongPollData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE eid = ?i", $myId)->getNumRows();

        Database::getInstance()->query("INSERT INTO eviger.eviger_longpoll_data (personalIdEvent, eid, type, dataSerialized) VALUES (?i, ?i, 1, '?s')", $personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1, $myId, serialize(["eventId" => (int)($personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1), "eventType" => "newMessage", "objects" => ["id" => 1, "out_id" => (int)$myId, "peer_id" => (int)$idParsed, "message" => Other::encryptMessage($text), "date" => $time]]));

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["id" => 1])
            ->toJson();

    }

    /**
     * @param string $id
     * @param string $token
     * @return string
     * @throws selfThrows|MySqlException
     */
    public static function getHistory(string $id, string $token): string
    {

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($id)) ? 0 : $id, $id);

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "id invalid"]);

        $idParsed = (int)$selectAllOfUserObject->fetchAssoc()['id'];

        $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $dialogData = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers LIKE '%?S%'", $myId);

        $foundedPeers = ($idParsed === $myId) ? "$myId,$myId" : null;

        while ($dialogParsed = $dialogData->fetchAssoc()) {
            preg_match_all("/[^$myId,]+/", $dialogParsed['peers'], $matches);
            if (isset($matches[0][0])) {
                $foundedPeers = $dialogParsed['peers'];
                break;
            }
            $foundedPeers = null;
        }

        $dataArray = [];

        if ($foundedPeers !== null) {

            $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_messages WHERE peers = '?s'", $foundedPeers);

            while ($data_parsed = $data->fetchAssoc()) {
                $dataArray[] = ["id" => (int)$data_parsed['local_id_message'], "out" => $data_parsed['out_id'] === Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'], "peer_id" => $data_parsed['peer_id'], "message" => Other::decryptMessage($data_parsed['message']), "date" => (int)$data_parsed['date']];
            }

        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse($dataArray)
            ->toJson();

    }

    /**
     * @param string $token
     * @return string
     * @throws MySqlException
     */
    public static function getDialogs(string $token): string
    {

        $myId = Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];
        $dataArray = [];
        $dialogsData = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE peers LIKE '%?S%' ORDER BY last_message_date DESC", $myId);

        while ($dialogsParsed = $dialogsData->fetchAssoc()) {
            preg_match_all("/[^$myId,]+/", $dialogsParsed['peers'], $matches);
            $peer_id = $dialogsParsed['peers'] === "$myId,$myId" ? (int)$myId : (int)$matches[0][0];
            $dataArray[] = ["id" => (int)$dialogsParsed['last_message_id'], "peer_id" => $peer_id, "out_id" => (int)$dialogsParsed['last_message_sender'], "message" => Other::decryptMessage($dialogsParsed['last_message']), "date" => (int)$dialogsParsed['last_message_date']];
        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse($dataArray)
            ->toJson();

    }

}