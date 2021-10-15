<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Error;
use Eviger\Api\DTO\Get;
use Eviger\Api\Tools\Other;
use Eviger\Database;

class Users
{

    /**
     * @param string $token
     * @param string|null $id
     * @return Get
     * @throws Error
     */
    public static function get(string $token, ?string $id = NULL): Get
    {
        if ($id === NULL) {

            return (new Get)
                ->setEid((int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])
                ->setUsername((string)Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['username'])
                ->setLastSeen((int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['lastSeen'])
                ->setEmail(Database::getInstance()->query("SELECT email FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['email'])
            ;
//            return Other::generateJson(
//                ["response" => [
//                    "eid" => (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'],
//                    "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['username'],
//                    "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['lastSeen'],
//                    "email" => Database::getInstance()->query("SELECT email FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['email']
//                ]
//                ]);

        }

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($id)) ? 0 : $id, $id);

        if (!$selectAllOfUserObject->getNumRows()) {
            return throw new Error("Id is incorrect");
//            return Other::generateJson(["response" => ["error" => "id incorrect"]]);
        }

        $idParsed = $selectAllOfUserObject->fetchAssoc()['id'];

        return (new Get())
            ->setEid((int)$idParsed)
            ->setUsername(Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['username'])
            ->setLastSeen((int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['lastSeen']);
//
//        return Other::generateJson(
//            [
//                "response" => [
//                    "eid" => (int)$idParsed,
//                    "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['username'],
//                    "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['lastSeen']
//                ]
//            ]);
    }

    /**
     * @param string $query
     * @return Get[]
     */
    public static function search(string $query): array
    {
        /** @var Get[] $dataFromDatabase */
        $dataFromDatabase = [];
        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username LIKE '%?S%'", $query);

        while ($data_parsed = $data->fetchAssoc()) {

            $dataFromDatabase[] = (new Get)
                ->setEid((int)$data_parsed['id'])
                ->setUsername($data_parsed['username'])
                ->setLastSeen((int)$data_parsed['lastSeen']);
//            $dataFromDatabase[] = ["eid" => (int)$data_parsed['id'],
//                "username" => $data_parsed['username'],
//                "lastSeen" => (int)$data_parsed['lastSeen']];

        }

        return $dataFromDatabase;
//        return Other::generateJson(["response" => $a]);
    }
}