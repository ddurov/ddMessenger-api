<?php

namespace Eviger\Api\Tools;

use Eviger\Database;

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
        $ciphertext_raw = openssl_encrypt($message, $cipher, $_ENV["hashKey"], OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $_ENV["hashKey"], true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    /**
     * @param string $messageEncoded
     * @return string
     */
    public static function decryptMessage(string $messageEncoded): string
    {
        $c = base64_decode($messageEncoded);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $text_encrypted_raw = substr($c, $ivlen + 32);
        return openssl_decrypt($text_encrypted_raw, $cipher, $_ENV["hashKey"], OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $token
     * @return string
     */
    public static function checkToken(string $token): string
    {

        if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_tokens WHERE token = '?s'", $token)->getNumRows()) return self::generateJson(["response" => ["error" => "token not found"]]);

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_deactivated_accounts WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->getNumRows()) return self::generateJson(["response" => ["error" => "account deactivated", "canRestoreNow" => true]]);

        $bans = Database::getInstance()->query("SELECT * FROM eviger.eviger_bans WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

        if ($bans->getNumRows()) {

            switch ($bans->fetchAssoc()['type']) {

                case 1:
                    return self::generateJson(
                        ["response" =>
                            ["error" => "account banned",
                                "details" => ["reason" => $bans->fetchAssoc()['reason'], "canRestoreNow" => (time() > $bans->fetchAssoc()['time_unban'])]
                            ]
                        ]
                    );
                case 2:
                    return self::generateJson(
                        ["response" =>
                            ["error" => "account banned",
                                "details" => ["reason" => $bans->fetchAssoc()['reason'], "canRestoreNow" => false]
                            ]
                        ]
                    );

            }

        }
        return true;

    }

    /**
     * @param string $token
     * @return bool
     */
    public static function checkAdmin(string $token): bool
    {

        if (Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] !== 1) http_response_code(404);

        if (self::checkToken($token))

            if (Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE login = '?s'", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] !== 1) return false;

        return true;

    }

    /**
     * @param string $message
     */
    public static function log(string $message): void
    {
        if (!file_exists('/var/log/API/')) {
            mkdir('/var/log/API/', 0777, true);
        }
        $time = date('D M j G:i:s');
        file_put_contents("/var/log/API/error.log", "[$time] " . $message);
    }
}