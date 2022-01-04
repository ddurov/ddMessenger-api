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
     * @param string|int $toId
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

        $idParsed = (int)$selectAllOfUserObject->fetchAssoc()['id'];

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "to_id invalid"]);

        $dialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE (firstId = ?i AND secondId = ?i) OR (secondId = ?i AND firstId = ?i)", $myId, $idParsed, $myId, $idParsed);

        $fetchedDialog = $dialog->fetchAssoc();

        $time = time();

        if ($dialog->getNumRows()) {

            $messageId = $fetchedDialog['lastMessageId'] + 1;

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (dialogId, senderId, messageId, message, messageDate) VALUES (?i, ?i, ?i, '?s', ?i)",
                $fetchedDialog['id'],
                $myId,
                $messageId,
                Other::encryptMessage($text),
                $time);

            Database::getInstance()->query("UPDATE eviger.eviger_dialogs SET lastMessageId = ?i, lastMessage = '?s', lastMessageDate = ?i WHERE id = ?i",
                $messageId,
                Other::encryptMessage($text),
                $time,
                $fetchedDialog['id']);

            $personalId = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE peers LIKE '%?i%' AND peers LIKE '%?i%'", $myId, $idParsed)->getNumRows();

            Database::getInstance()->query("INSERT INTO eviger.eviger_longpoll_data (personalIdEvent, type, peers, dataSerialized, whoChecked) VALUES (?i, 1, '?s', '?s', '?s')",
                $personalId === 0 ? 1 : $personalId + 1,
                "$myId,$idParsed",
                serialize(["eventId" => (int)((int)$personalId === 0 ? 1 : $personalId + 1),
                    "eventType" => "newMessage",
                    "objects" => ["id" => $messageId,
                        "peerId" => -1, // don't remove
                        "senderId" => $myId,
                        "message" => Other::encryptMessage($text),
                        "date" => $time
                    ]
                ]),
                serialize([]));

            return (new Response)
                ->setStatus("ok")
                ->setResponse(["id" => (int)$messageId])
                ->toJson();

        } else {

            Database::getInstance()->query("INSERT INTO eviger.eviger_messages (dialogId, senderId, messageId, message, messageDate) VALUES (?i, ?i, ?i, '?s', ?i)",
                Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs")->getNumRows()+1,
                $myId,
                1,
                Other::encryptMessage($text),
                $time);

            Database::getInstance()->query("INSERT INTO eviger.eviger_dialogs (firstId, secondId, lastMessageId, lastMessage, lastMessageDate) VALUES (?i, ?i, ?i, '?s', ?i)",
                $myId,
                $idParsed,
                1,
                Other::encryptMessage($text),
                $time);

            $personalId = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE peers LIKE '%?i%' AND peers LIKE '%?i%'", $myId, $idParsed)->getNumRows();

            Database::getInstance()->query("INSERT INTO eviger.eviger_longpoll_data (personalIdEvent, type, peers, dataSerialized, whoChecked) VALUES (?i, 1, '?s', '?s', '?s')",
                $personalId === 0 ? 1 : $personalId + 1,
                "$myId,$idParsed",
                serialize(["eventId" => (int)((int)$personalId === 0 ? 1 : $personalId + 1),
                    "eventType" => "newMessage",
                    "objects" => ["id" => 1,
                        "peerId" => -1, // don't remove
                        "senderId" => $myId,
                        "message" => Other::encryptMessage($text),
                        "date" => $time
                    ]
                ]),
                serialize([]));

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

        $dialog = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE (firstId = ?i AND secondId = ?i) OR (secondId = ?i AND firstId = ?i)", $myId, $idParsed, $myId, $idParsed);

        $fetchedDialog = $dialog->fetchAssoc();

        $getAllMessages = Database::getInstance()->query("SELECT * FROM eviger.eviger_messages WHERE dialogId = ?i", $fetchedDialog['id']);

        $dialogData = [];

        while ($message = $getAllMessages->fetchAssoc()) {
            $dialogData[] = ["out" => (int)$message['senderId'] === $myId,
                "peerId" => (int)$fetchedDialog['firstId'] === $myId ? (int)$fetchedDialog['secondId'] : (int)$fetchedDialog['firstId'],
                "messageId" => (int)$message['messageId'],
                "message" => Other::decryptMessage($message['message']),
                "messageDate" => (int)$message['messageDate']];
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

        $dialogs = Database::getInstance()->query("SELECT * FROM eviger.eviger_dialogs WHERE firstId = ?i OR secondId = ?i ORDER BY lastMessageDate DESC", $myId, $myId);

        while ($dialog = $dialogs->fetchAssoc()) {
            $dataArray[] = ["peerId" => (int)$dialog['firstId'] === $myId ? (int)$dialog['secondId'] : (int)$dialog['firstId'],
                "lastMessageId" => (int)$dialog['lastMessageId'],
                "lastMessage" => Other::decryptMessage($dialog['lastMessage']),
                "lastMessageDate" => (int)$dialog['lastMessageDate']];
        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse($dataArray)
            ->toJson();

    }

}