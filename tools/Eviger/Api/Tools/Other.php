<?php

declare(strict_types=1);

namespace Eviger\Api\Tools;

use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use Krugozor\Database\MySqlException;
use RuntimeException;

class Other
{

    /**
     * @param string $message
     * @return string
     */
    public static function encryptMessage(string $message): string
    {
        $ivLength = openssl_cipher_iv_length($cipher = "aes-128-cbc");
        $iv = openssl_random_pseudo_bytes($ivLength);
        $ciphertext_raw = openssl_encrypt($message, $cipher, getenv("HASH_KEY"), OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, getenv("HASH_KEY"), true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    /**
     * @param string $messageEncoded
     * @return string
     */
    public static function decryptMessage(string $messageEncoded): string
    {
        $c = base64_decode($messageEncoded);
        $ivLength = openssl_cipher_iv_length($cipher = "aes-128-cbc");
        $iv = substr($c, 0, $ivLength);
        substr($c, $ivLength, $sha2len = 32);
        $cipherTextRaw = substr($c, $ivLength + $sha2len);
        return openssl_decrypt($cipherTextRaw, $cipher, getenv("HASH_KEY"), OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $token
     * @return bool
     * @throws selfThrows|MySqlException
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
     * @throws selfThrows|MySqlException
     */
    public static function checkAdmin(string $token): bool
    {

        if (Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] !== 1) {
            http_response_code(404);
        }

        return !(self::checkToken($token) && Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE login = '?s'", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] !== 1);

    }

    /**
     * @param mixed $message
     */
    public static function log($message): void
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
    public static function postUsageMethod(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") throw new selfThrows(["message" => "this method usage only POST requests"]);
    }
}