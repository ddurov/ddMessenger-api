<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use Krugozor\Database\MySqlException;

class Users
{

    /**
     * @param string $token
     * @param string|null $id
     * @return string
     * @throws selfThrows|MySqlException
     */
    public static function get(string $token, ?string $id = NULL): string
    {
        if ($id === NULL) {

            $idByToken = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'];

            return (new Response)
                ->setStatus("ok")
                ->setResponse([
                    "eid" => $idByToken,
                    "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", $idByToken)->fetchAssoc()['username'],
                    "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", $idByToken)->fetchAssoc()['lastSeen'],
                    "email" => Database::getInstance()->query("SELECT email FROM eviger.eviger_users WHERE id = ?i", $idByToken)->fetchAssoc()['email']
                ])
                ->toJson();

        }

        $selectAllOfUserObject = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE eviger_users.id = ?i OR eviger_users.username = '?s'", (!is_numeric($id)) ? 0 : $id, $id);

        if (!$selectAllOfUserObject->getNumRows()) throw new selfThrows(["message" => "id invalid"]);

        $idParsed = (int)$selectAllOfUserObject->fetchAssoc()['id'];

        return (new Response)
            ->setStatus("ok")
            ->setResponse([
                "eid" => $idParsed,
                "username" => Database::getInstance()->query("SELECT username FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['username'],
                "lastSeen" => (int)Database::getInstance()->query("SELECT lastSeen FROM eviger.eviger_users WHERE id = ?i", $idParsed)->fetchAssoc()['lastSeen']
            ])
            ->toJson();
    }

    /**
     * @param string $query
     * @return string
     * @throws MySqlException
     */
    public static function search(string $query): string
    {
        $dataFromDatabase = [];
        $dataNotParsed = Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username LIKE \"%?S%\"", $query);

        while ($dataParsed = $dataNotParsed->fetchAssoc()) {

            $dataFromDatabase[] = [
                "eid" => (int)$dataParsed['id'],
                "username" => $dataParsed['username'],
                "lastSeen" => (int)$dataParsed['lastSeen']
            ];

        }

        return (new Response)
            ->setStatus("ok")
            ->setResponse($dataFromDatabase)
            ->toJson();
    }
}