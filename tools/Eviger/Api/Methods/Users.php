<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;

class Users
{
    /**
     * @param string $token
     * @param string|null $id
     * @return string
     */
    public static function get(string $token, ?string $id = NULL): string
    {
        if ($id === NULL) {

            return Other::generateJson(
                ["response" => [
                    "eid" => (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'],
                    "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['username'],
                    "online" => (int)Database::getInstance()->query("SELECT online FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['online'],
                    "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['lastSeen'],
                    "email" => Database::getInstance()->query("SELECT email FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['email']
                ]
                ]);

        } else {

            $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($id)) ? 0 : $id, $id);

            if (!$selectAllOfUserObject->getNumRows()) return Other::generateJson(["response" => ["error" => "id incorrect"]]);

            $idParsed = $selectAllOfUserObject->fetchAssoc()['id'];

            return Other::generateJson(
                ["response" => [
                    "eid" => (int)$idParsed,
                    "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['username'],
                    "online" => (int)Database::getInstance()->query("SELECT online FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['online'],
                    "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['lastSeen']
                ]
                ]);

        }
    }

    /**
     * @param string $query
     * @return string
     */
    public static function search(string $query): string
    {
        $a = [];
        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username LIKE '%?S%'", $query);

        while ($data_parsed = $data->fetchAssoc()) {

            $a[] = ["eid" => (int)$data_parsed['id'], "username" => $data_parsed['username'], "online" => (int)$data_parsed['online'], "lastSeen" => (int)$data_parsed['lastSeen']];

        }

        return Other::generateJson(["response" => $a]);
    }
}