<?php

declare(strict_types=1);

namespace Eviger\Api\Tools;

use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use RuntimeException;

class Other
{

    /**
     * @param array $array
     * @return string
     */
    public static function generateJson(array $array): string
    {
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $message
     * @return string
     */
    public static function encryptMessage(string $message): string
    {
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($message, $cipher, getenv("hashKey"), OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, getenv("hashKey"), true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    /**
     * @param string $messageEncoded
     * @return string
     */
    public static function decryptMessage(string $messageEncoded): string
    {
        $c = base64_decode($messageEncoded);
        $ivLength = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivLength);
        $text_encrypted_raw = substr($c, $ivLength + 32);
        return openssl_decrypt($text_encrypted_raw, $cipher, getenv("hashKey"), OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $token
     * @return bool
     * @throws selfThrows
     */
    public static function checkToken(string $token): bool
    {

        if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_tokens WHERE token = '?s'", $token)->getNumRows()) throw new selfThrows(["message" => "token not found"]);

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_deactivated_accounts WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->getNumRows()) throw new selfThrows(["message" => "account deactivated", "canRestoreNow" => true]);

        $bans = Database::getInstance()->query("SELECT * FROM eviger.eviger_bans WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

        if ($bans->getNumRows()) {

            switch ($bans->fetchAssoc()['type']) {

                case 1:
                    throw new selfThrows(["message" => "account banned", "details" => ["reason" => $bans->fetchAssoc()['reason'], "canRestoreNow" => (time() > $bans->fetchAssoc()['time_unban'])]]);
                case 2:
                    throw new selfThrows(["message" => "account banned", "details" => ["reason" => $bans->fetchAssoc()['reason'], "canRestoreNow" => false]]);

            }

        }

        return true;

    }

    /**
     * @param string $token
     * @return bool
     * @throws selfThrows
     */
    public static function checkAdmin(string $token): bool
    {

        if (Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] !== 1) {
            http_response_code(404);
        }

        return !(self::checkToken($token) && Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE login = '?s'", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] !== 1);

    }

    /**
     * @param string $message
     */
    public static function log(string $message): void
    {
        if (!file_exists('/var/log/API/') && !mkdir('/var/log/API/', 0777, true) && !is_dir('/var/log/API/')) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', '/var/log/API/'));
        }
        $time = date('D M j G:i:s');
        file_put_contents("/var/log/API/error.log", "[$time]: $message\n", FILE_APPEND);
    }

    /**
     * @throws selfThrows
     */
    public static function postUsageMethod(): bool
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") throw new selfThrows(["message" => "this method usage only POST requests"]);
        return true;
    }

    /**
     * @param $mixedData
     * @throws selfThrows
     */
    public static function local_checkLoginAndPassword($mixedData): void
    {
        // login checks

        if (!isset($mixedData['login'])) throw new selfThrows(["message" => "login parameter is missing"]);

        if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) throw new selfThrows(["message" => "the login is too big or too small"]);

        if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) throw new selfThrows(["message" => "the login must contain a-z, A-Z, 0-9 and _"]);

        if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) throw new selfThrows(["message" => "user not found"]);

        // password checks

        if (!isset($mixedData['password'])) throw new selfThrows(["message" => "password parameter is missing"]);

        if ((mb_strlen($mixedData['password']) <= 8 || $mixedData['password'] >= 64)) throw new selfThrows(["message" => "the password is too big or too small"]);

        if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) throw new selfThrows(["message" => "the password must contain a-z, A-Z, 0-9 and _"]);
    }
}