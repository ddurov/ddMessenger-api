<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use Exception;
use Krugozor\Database\MySqlException;
use Mobile_Detect;

class User
{
    /**
     * @param string $login
     * @param string $password
     * @param string $email
     * @param string|null $username
     * @param string $emailCode
     * @param string $hashCode
     * @return string
     * @throws Exception
     */
    public static function registerAccount(string $login, string $password, string $email, ?string $username, string $emailCode, string $hashCode): string
    {

        $salt = bin2hex(random_bytes(16));
        $getCodeEmailStatus = json_decode(Email::confirmCode($email, $emailCode, $hashCode), true);

        if ($getCodeEmailStatus['response'] !== true) throw new selfThrows(["message" => $getCodeEmailStatus['response']['error']], http_response_code());

        $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);

        Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);

        Database::getInstance()->query("INSERT INTO eviger_users (login, passwordHash, passwordSalt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')",
            $login,
            md5($password . $salt),
            $salt,
            $username === null ? "eid" . (Database::getInstance()->query("SELECT * FROM eviger_users")->getNumRows() + 1) : $username,
            $email);

        $myId = (int)Database::getInstance()->query("SELECT id FROM eviger_users WHERE login = '?s'", $login)->fetchAssoc()['id'];

        Database::getInstance()->query("INSERT INTO eviger_tokens (eid, token) VALUES (?i, '?s')", $myId, $token);
        Database::getInstance()->query("INSERT INTO eviger_sessions (eid, authTime, authDeviceType, authIp) VALUES ('?s', ?i, ?i, '?s')",
            $myId,
            time(),
            ((new Mobile_Detect)->isMobile() || (new Mobile_Detect)->isTablet()) ? 2 : 1,
            $_SERVER['REMOTE_ADDR']);

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["token" => $token])
            ->toJson();

    }

    /**
     * @param string $login
     * @param string $password
     * @return string
     * @throws selfThrows|MySqlException
     */
    public static function auth(string $login, string $password): string
    {

        $salt = Database::getInstance()->query("SELECT passwordSalt FROM eviger.eviger_users WHERE login = '?s'", $login)->fetchAssoc()['passwordSalt'];

        if (md5($password . $salt) !== Database::getInstance()->query("SELECT passwordHash FROM eviger_users WHERE login = '?s'", $login)->fetchAssoc()['passwordHash']) throw new selfThrows(["message" => "invalid login or password"], 400);

        if (Database::getInstance()->query("SELECT * FROM eviger_attempts_auth WHERE login = '?s'", $login)->getNumRows() >= 5) {

            // TODO: Ban user

            // $dataOfBannedProfile = Database::getInstance()->query("SELECT * FROM eviger.eviger_bans WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_users WHERE login = '?s'", $login)->fetchAssoc()['eid']);
            // return Other::generateJson(["response" => ["error" => "account banned", "details" => ["reason" => $dataOfBannedProfile->fetchAssoc()['reason'], "canRestore" => (time() > $dataOfBannedProfile->fetchAssoc()['time_unban'])]]]);

        }

        $myId = Database::getInstance()->query("SELECT id FROM eviger_users WHERE login = '?s'", $login)->fetchAssoc()['id'];
        $token = Database::getInstance()->query("SELECT * FROM eviger_tokens WHERE eid = ?i", $myId)->fetchAssoc()['token'];

        Database::getInstance()->query("INSERT INTO eviger_sessions (eid, authTime, authDeviceType, authIp) VALUES ('?s', ?i, ?i, '?s')",
            $myId,
            time(),
            ((new Mobile_Detect)->isMobile() || (new Mobile_Detect)->isTablet()) ? 2 : 1,
            $_SERVER['REMOTE_ADDR']);
        Database::getInstance()->query("INSERT INTO eviger_attempts_auth (login, time, authorizationIp) VALUES ('?s', ?i, '?s')", $login, time(), $_SERVER['REMOTE_ADDR']);

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["token" => $token])
            ->toJson();

    }

    /**
     * @param string $email
     * @param string $newPassword
     * @return string
     * @throws Exception
     */
    public static function resetPassword(string $email, string $newPassword): string
    {

        $salt = bin2hex(random_bytes(16));
        $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);
        $idAccount = Database::getInstance()->query("SELECT id FROM eviger_users WHERE email = '?s'", $email)->fetchAssoc()['id'];

        Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
        Database::getInstance()->query("UPDATE eviger_users SET passwordHash = '?s', passwordSalt = '?s' WHERE id = ?i",
            md5($newPassword . $salt),
            $salt,
            $idAccount);
        Database::getInstance()->query("UPDATE eviger_tokens SET token = '?s' WHERE eid = ?i", $token, $idAccount);

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["token" => $token])
            ->toJson();

    }

    /**
     * @param string $token
     * @return string
     * @throws MySqlException
     */
    public static function setOnline(string $token): string
    {

        Database::getInstance()->query("UPDATE eviger.eviger_users SET lastSeen = 1, lastSentOnline = ?i WHERE id = ?i",
            time(),
            Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

        return (new Response)
            ->setStatus("ok")
            ->toJson();

    }

    /**
     * @param string $newName
     * @param string $email
     * @param string $codeEmail
     * @param string $hashCode
     * @return string
     * @throws selfThrows|MySqlException
     */
    public static function changeName(string $newName, string $email, string $codeEmail, string $hashCode): string
    {

        $getCodeEmailStatus = json_decode(Email::confirmCode($email, $codeEmail, $hashCode), true);

        if ($getCodeEmailStatus['response'] !== true) throw new selfThrows(["message" => $getCodeEmailStatus['response']['error']], http_response_code());

        if (preg_match("/^e?id+[\d]+/u", $newName)) throw new selfThrows(["message" => "newName cannot contain the prefix eid or id"], 400);

        Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
        Database::getInstance()->query("UPDATE eviger.eviger_users SET username = '?s' WHERE email = '?s'", $newName, $email);
        return (new Response)
            ->setStatus("ok")
            ->toJson();

    }

}