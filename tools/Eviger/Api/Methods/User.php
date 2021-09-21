<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;
use Exception;
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
     */
    public static function registerAccount(string $login, string $password, string $email, ?string $username, string $emailCode, string $hashCode): string
    {

        try {

            $salt = bin2hex(random_bytes(8));
            $getCodeEmailStatus = json_decode(Email::confirmCode($email, $emailCode, $hashCode), true);

            if ($getCodeEmailStatus['response'] === true) {

                $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);

                Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
                Database::getInstance()->query("INSERT INTO eviger_sessions (login, date_auth, session_type_device, ip_device) VALUES ('?s', ?i, ?i, '?s')", $login, time(), ((new Mobile_Detect)->isMobile() || (new Mobile_Detect)->isTablet()) ? 2 : 1, $_SERVER['REMOTE_ADDR']);

                if ($username !== null) {

                    Database::getInstance()->query("INSERT INTO eviger_users (login, password_hash, password_salt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')", $login, md5($password . $salt), $salt, $username, $email);

                } else {

                    Database::getInstance()->query("INSERT INTO eviger_users (login, password_hash, password_salt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')", $login, md5($password . $salt), $salt, NULL, $email);
                    Database::getInstance()->query("UPDATE eviger_users SET username = 'eid?i' WHERE login = '?s'", Database::getInstance()->query("SELECT * FROM eviger_users")->getNumRows(), $login);

                }

                Database::getInstance()->query("INSERT INTO eviger_tokens (eid, token) VALUES (?i, '?s')", (int)Database::getInstance()->query("SELECT id FROM eviger_users WHERE login = '?s'", $login)->fetchAssoc()['id'], $token);

                return Other::generateJson(["response" => ["status" => "ok", "token" => $token]]);

            } else {

                return Other::generateJson(["response" => ["error" => $getCodeEmailStatus['response']['error']]]);

            }

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

    /**
     * @param string $email
     * @param string $newPassword
     * @return string
     */
    public static function restorePassword(string $email, string $newPassword): string
    {

        try {

            $salt = bin2hex(random_bytes(8));
            $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);
            $idAccount = Database::getInstance()->query("SELECT id FROM eviger_users WHERE email = '?s'", $email)->fetchAssoc()['id'];
            Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
            Database::getInstance()->query("UPDATE eviger_users SET password_hash = '?s', password_salt = '?s' WHERE id = ?i", md5($newPassword . $salt), $salt, $idAccount);
            Database::getInstance()->query("UPDATE eviger_tokens SET token = '?s' WHERE eid = ?i", $token, $idAccount);
            return Other::generateJson(["response" => ["status" => "ok", "newToken" => $token]]);

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

    /**
     * @param string $token
     * @return string
     */
    public static function setOnline(string $token): string
    {

        Database::getInstance()->query("UPDATE eviger.eviger_users SET online = 1, lastSeen = 0 WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);
        return Other::generateJson(["response" => ["status" => "ok"]]);

    }

    /**
     * @param string $token
     * @return string
     */
    public static function setOffline(string $token): string
    {

        Database::getInstance()->query("UPDATE eviger.eviger_users SET online = 0, lastSeen = ?i WHERE id = ?i", time(), Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);
        return Other::generateJson(["response" => ["status" => "ok"]]);

    }

    public static function changeName(string $newName, string $email, string $codeEmail, string $hashCode): string
    {

        $getCodeEmailStatus = json_decode(Email::confirmCode($email, $codeEmail, $hashCode), true);

        if ($getCodeEmailStatus['response'] === true) {

            if (preg_match("/^(e)?id.*/gu", $newName)) die(Other::generateJson(["response" => ["error" => "username cannot contain the prefix eid or id"]]));

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $newName)->getNumRows()) return Other::generateJson(["response" => ["error" => "username is busy"]]);

            Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $email);
            Database::getInstance()->query("UPDATE eviger.eviger_users SET username = '?s' WHERE email = '?s'", $newName, $email);
            return Other::generateJson(["response" => ["status" => "ok"]]);

        } else {

            return Other::generateJson(["response" => ["error" => $getCodeEmailStatus['response']['error']]]);

        }

    }

    /**
     * @param string $login
     * @param string $password
     * @return string
     */
    public static function auth(string $login, string $password): string
    {

        $salt = Database::getInstance()->query("SELECT password_salt FROM eviger.eviger_users WHERE login = '?s'", $login)->fetchAssoc()['password_salt'];

        if (md5($password . $salt) == Database::getInstance()->query("SELECT password_hash FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['password_hash']) {

            if (Database::getInstance()->query("SELECT * FROM eviger_attempts_auth WHERE login = '?s'", $login)->getNumRows() >= 5) {

                // TODO: Ban user
                return Other::generateJson(["response" => ["error" => "too many authorizations, account has been frozen"]]);

            } else {

                $token = Database::getInstance()->query("SELECT * FROM eviger_tokens WHERE eid = ?i", Database::getInstance()->query("SELECT id FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['id'])->fetchAssoc()['token'];

                Database::getInstance()->query("INSERT INTO eviger_sessions (login, date_auth, session_type_device, ip_device) VALUES ('?s', ?i, ?i, '?s')", $login, time(), ((new Mobile_Detect)->isMobile() || (new Mobile_Detect)->isTablet()) ? 2 : 1, $_SERVER['REMOTE_ADDR']);
                Database::getInstance()->query("INSERT INTO eviger_attempts_auth (login, time, auth_ip) VALUES ('?s', ?i, '?s')", $login, time(), $_SERVER['REMOTE_ADDR']);

                return Other::generateJson(["response" => ["status" => "ok", "token" => $token]]);

            }

        } else {

            return Other::generateJson(["response" => ["error" => "invalid login or password"]]);

        }

    }

}