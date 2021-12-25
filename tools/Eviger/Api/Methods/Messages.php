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
     * @param $toId
     * @param string $text
     * @param string $token
     * @return string
     * @throws MySqlException
     * @throws selfThrows
     */
    public static function send($toId, string $text, string $token): string
    {

        $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($toId)) ? 0 : $toId, $toId);

        if (!$selectAllOfUserObject->getNumRows() || $toId === $myId) throw new selfThrows(["message" => "to_id invalid"]);

        $idParsed = (int)$selectAllOfUserObject->fetchAssoc()['id'];

        $dialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE firstId IN (?i, ?i) AND secondId IN (?i, ?i)", $myId, $idParsed, $myId, $idParsed);

        $time = time();

        $fetchedDialog = $dialog->fetchAssoc();

        if ($dialog->getNumRows()) {

            $messageId = $fetchedDialog['lastMessageId'] + 1;

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (fromId, peerId, messageId, message, messageDate) VALUES (?i, ?i, ?i, '?s', ?i)",
                $myId,
                $idParsed,
                $messageId,
                Other::encryptMessage($text),
                $time);

            Database::getInstance()->query("UPDATE eviger.eviger_dialogs SET lastMessageId = ?i, lastMessageSender = ?i, lastMessage = '?s', lastMessageDate = ?i WHERE id = ?i",
                $messageId,
                $myId,
                Other::encryptMessage($text),
                $time,
                $fetchedDialog['id']);

            $personalIdLongPollData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE fromEid IN (?i, ?i) AND toEid IN (?i, ?i)", $myId, $idParsed, $myId, $idParsed)->getNumRows();

            Database::getInstance()->query("INSERT INTO eviger.eviger_longpoll_data (personalIdEvent, fromEid, toEid, type, dataSerialized) VALUES (?i, ?i, ?i, 1, '?s')",
                $personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1,
                $myId,
                $idParsed,
                serialize(["eventId" => (int)($personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1),
                    "eventType" => "newMessage",
                    "objects" => ["id" => (int)$messageId,
                        "peer_id" => $myId,
                        "message" => Other::encryptMessage($text),
                        "date" => $time
                    ]
                ]));

            return (new Response)
                ->setStatus("ok")
                ->setResponse(["id" => (int)$messageId])
                ->toJson();

        } else {

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (fromId, peerId, messageId, message, messageDate) VALUES (?i, ?i, 1, '?s', ?i)",
                $myId,
                $idParsed,
                Other::encryptMessage($text),
                $time);

            Database::getInstance()->query("INSERT INTO eviger.eviger_dialogs (firstId, secondId, lastMessageId, lastMessageSender, lastMessage, lastMessageDate) VALUES (?i, ?i, 1, ?i, '?s', ?i)",
                $myId,
                $idParsed,
                $myId,
                Other::encryptMessage($text),
                $time);

            $personalIdLongPollData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE fromEid = ?i OR toEid = ?i", $idParsed, $idParsed)->getNumRows();

            Database::getInstance()->query("INSERT INTO eviger.eviger_longpoll_data (personalIdEvent, fromEid, toEid, type, dataSerialized) VALUES (?i, ?i, ?i, 1, '?s')",
                $personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1,
                $myId,
                $idParsed,
                serialize(["eventId" => (int)($personalIdLongPollData === 0 ? 1 : $personalIdLongPollData + 1),
                    "eventType" => "newMessage",
                    "objects" => ["id" => 1,
                        "peer_id" => $myId,
                        "message" => Other::encryptMessage($text),
                        "date" => $time
                    ]
                ]));

            return (new Response)
                ->setStatus("ok")
                ->setResponse(["id" => 1])
                ->toJson();

        }

    }

    /**
     * @param $id
     * @param string $token
     * @return string
     * @throws MySqlException
     * @throws selfThrows
     */
    public static function getHistory($id, string $token): string
    {

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($id)) ? 0 : $id, $id);

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "id invalid"]);

        $idParsed = (int)$selectAllOfUserObject->fetchAssoc()['id'];

        $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

        $dialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_messages WHERE fromId IN (?i, ?i) AND peerId IN (?i, ?i)", $myId, $idParsed, $myId, $idParsed);

        $dialogData = [];

        while ($tempData = $dialog->fetchAssoc()) {
            $dialogData[] = ["id" => (int)$tempData['messageId'],
                "out" => (int)$tempData['fromId'] === $myId,
                "peer_id" => ((int)$tempData['fromId'] === $myId) ? (int)$tempData['peerId'] : (int)$tempData['fromId'],
                "message" => Other::decryptMessage($tempData['message']),
                "date" => (int)$tempData['messageDate']];
        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse($dialogData)
            ->toJson();

    }

    /**
     * @param string $token
     * @return string
     * @throws MySqlException
     */
    public static function getDialogs(string $token): string
    {

        $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];
        $dataArray = [];
        $dialogsData = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE firstId = ?i OR secondId = ?i ORDER BY lastMessageDate DESC", $myId, $myId);

        while ($dialogsParsed = $dialogsData->fetchAssoc()) {
            $dataArray[] = ["id" => (int)$dialogsParsed['lastMessageId'],
                "peer_id" => ((int)$dialogsParsed['firstId'] === $myId) ? (int)$dialogsParsed['secondId'] : (int)$dialogsParsed['firstId'],
                "message" => Other::decryptMessage($dialogsParsed['lastMessage']),
                "date" => (int)$dialogsParsed['lastMessageDate']];
        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse($dataArray)
            ->toJson();

    }

}