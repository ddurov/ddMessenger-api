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
    public static function get(string $token, ?string $id = NULL): string {
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

            if (is_numeric($id)) {

                if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE id = ?i", $id)->getNumRows()) return Other::generateJson(["response" => ["error" => "user not found"]]);

                return Other::generateJson(
                    ["response" => [
                        "eid" => (int)Database::getInstance()->query("SELECT id FROM eviger.eviger_users WHERE id = ?i", $id)->fetchAssoc()['id'],
                        "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", $id)->fetchAssoc()['username'],
                        "online" => (int)Database::getInstance()->query("SELECT online FROM eviger.eviger_users WHERE id = ?i", $id)->fetchAssoc()['online'],
                        "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", $id)->fetchAssoc()['lastSeen']
                    ]
                ]);

            } else {

                if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $id)->getNumRows()) return Other::generateJson(["response" => ["error" => "user not found"]]);

                return Other::generateJson(
                    ["response" => [
                        "eid" => (int)Database::getInstance()->query("SELECT id FROM eviger.eviger_users WHERE username = '?s'", $id)->fetchAssoc()['id'],
                        "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE username = '?s'", $id)->fetchAssoc()['username'],
                        "online" => (int)Database::getInstance()->query("SELECT online FROM eviger.eviger_users WHERE username = '?s'", $id)->fetchAssoc()['online'],
                        "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE username = '?s'", $id)->fetchAssoc()['lastSeen']
                    ]
                ]);

            }

        }
    }

    /**
     * @param string $query
     * @return string
     */
    public static function search(string $query): string {
        $a = [];
        $data = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username LIKE '%?S%'", $query);

        while ($data_parsed = $data->fetchAssoc()) {

            $a[] = ["eid" => (int)$data_parsed['id'], "username" => $data_parsed['username'], "online" => (int)$data_parsed['online'], "lastSeen" => (int)$data_parsed['lastSeen']];

        }

        return Other::generateJson(["response" => $a]);
    }
}