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

        $salt = bin2hex(random_bytes(8));
        $getCodeEmailStatus = json_decode(Email::confirmCode($email, $emailCode, $hashCode), true);

        if ($getCodeEmailStatus['response'] !== true) throw new selfThrows(["message" => $getCodeEmailStatus['response']['error']]);

        $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);

        Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
        Database::getInstance()->query("INSERT INTO eviger_sessions (login, date_auth, session_type_device, ip_device) VALUES ('?s', ?i, ?i, '?s')", $login, time(), ((new Mobile_Detect)->isMobile() || (new Mobile_Detect)->isTablet()) ? 2 : 1, $_SERVER['REMOTE_ADDR']);

        if ($username !== null) {

            Database::getInstance()->query("INSERT INTO eviger_users (login, password_hash, password_salt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')", $login, md5($password . $salt), $salt, $username, $email);

        } else {

            Database::getInstance()->query("INSERT INTO eviger_users (login, password_hash, password_salt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')", $login, md5($password . $salt), $salt, "eid" . (Database::getInstance()->query("SELECT * FROM eviger_users")->getNumRows() + 1), $email);

        }

        Database::getInstance()->query("INSERT INTO eviger_tokens (eid, token) VALUES (?i, '?s')", (int)Database::getInstance()->query("SELECT id FROM eviger_users WHERE login = '?s'", $login)->fetchAssoc()['id'], $token);

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
    public static function restorePassword(string $email, string $newPassword): string
    {

        $salt = bin2hex(random_bytes(8));
        $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);
        $idAccount = Database::getInstance()->query("SELECT id FROM eviger_users WHERE email = '?s'", $email)->fetchAssoc()['id'];

        Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
        Database::getInstance()->query("UPDATE eviger_users SET password_hash = '?s', password_salt = '?s' WHERE id = ?i", md5($newPassword . $salt), $salt, $idAccount);
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

        Database::getInstance()->query("UPDATE eviger.eviger_users SET lastSeen = 1, lastSendedOnline = ?i WHERE id = ?i", time(), Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);
        return (new Response)
            ->setStatus("ok")
            ->toJson();

    }

    /**
     * @param string $token
     * @return string
     * @throws MySqlException
     */
    public static function setOffline(string $token): string
    {

        Database::getInstance()->query("UPDATE eviger.eviger_users SET lastSeen = 1 WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);
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

        if ($getCodeEmailStatus['response'] !== true) throw new selfThrows(["message" => $getCodeEmailStatus['response']['error']]);

        if (preg_match("/^e?id+[\d]+/u", $newName)) throw new selfThrows(["message" => "newName cannot contain the prefix eid or id"]);

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $newName)->getNumRows()) throw new selfThrows(["message" => "newName is busy"]);

        Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
        Database::getInstance()->query("UPDATE eviger.eviger_users SET username = '?s' WHERE email = '?s'", $newName, $email);
        return (new Response)
            ->setStatus("ok")
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

        $salt = Database::getInstance()->query("SELECT password_salt FROM eviger.eviger_users WHERE login = '?s'", $login)->fetchAssoc()['password_salt'];

        if (md5($password . $salt) !== Database::getInstance()->query("SELECT password_hash FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['password_hash']) throw new selfThrows(["message" => "invalid login or password"]);

        if (Database::getInstance()->query("SELECT * FROM eviger_attempts_auth WHERE login = '?s'", $login)->getNumRows() >= 5) {

            // TODO: Ban user

            // $dataOfBannedProfile = Database::getInstance()->query("SELECT * FROM eviger.eviger_bans WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_users WHERE login = '?s'", $login)->fetchAssoc()['eid']);
            // return Other::generateJson(["response" => ["error" => "account banned", "details" => ["reason" => $dataOfBannedProfile->fetchAssoc()['reason'], "canRestore" => (time() > $dataOfBannedProfile->fetchAssoc()['time_unban'])]]]);

        }

        $token = Database::getInstance()->query("SELECT * FROM eviger_tokens WHERE eid = ?i", Database::getInstance()->query("SELECT id FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['id'])->fetchAssoc()['token'];

        Database::getInstance()->query("INSERT INTO eviger_sessions (login, date_auth, session_type_device, ip_device) VALUES ('?s', ?i, ?i, '?s')", $login, time(), ((new Mobile_Detect)->isMobile() || (new Mobile_Detect)->isTablet()) ? 2 : 1, $_SERVER['REMOTE_ADDR']);
        Database::getInstance()->query("INSERT INTO eviger_attempts_auth (login, time, auth_ip) VALUES ('?s', ?i, '?s')", $login, time(), $_SERVER['REMOTE_ADDR']);

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["token" => $token])
            ->toJson();

    }

}