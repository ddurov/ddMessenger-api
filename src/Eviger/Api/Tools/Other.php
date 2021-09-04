<?php

namespace Eviger\Api\Tools;

use Dotenv\Dotenv;
use Eviger\Database;
use Krugozor\Database\MySqlException;

class Other
{

    /**
     * @param array $array
     * @return false|string
     */
    public static function generateJson(array $array): string {
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $message
     * @return string
     */
    public static function encryptMessage(string $message): string {
        Dotenv::createImmutable("/var/www/tools")->load();
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($message, $cipher, $_ENV["hashKey"], OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $_ENV["hashKey"], true);
        return base64_encode($iv.$hmac.$ciphertext_raw);
    }

    /**
     * @param string $messageEncoded
     * @return string
     */
    public static function decryptMessage(string $messageEncoded): string {
        Dotenv::createImmutable("/var/www/tools")->load();
        $c = base64_decode($messageEncoded);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $text_encrypted_raw = substr($c, $ivlen+32);
        return openssl_decrypt($text_encrypted_raw, $cipher, $_ENV["hashKey"], OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $token
     * @throws MySqlException
     */
    public static function checkToken(string $token) {

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_tokens WHERE token = '?s'", $token)->getNumRows()) {

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_deactivated_accounts WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->getNumRows()) die(sendJson(["response" => ["error" => "account deactivated", "canRestoreNow" => true]]));

            $bans = Database::getInstance()->query("SELECT * FROM eviger.eviger_bans WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);

            if ($bans->getNumRows()) {

                switch ($bans->fetchAssoc()['type']) {

                    case 1:
                        die(
                            self::generateJson(
                                ["response" =>
                                    ["error" => "account banned",
                                        "details" => ["reason" => $bans->fetchAssoc()['reason']],
                                        "canRestoreNow" => $bans->fetchAssoc()['time_unban'] > time()
                                    ]
                                ]
                            )
                        );
                    case 2:
                        die(
                            self::generateJson(
                                ["response" =>
                                    ["error" => "account banned",
                                        "details" => ["reason" => $bans->fetchAssoc()['reason']],
                                        "canRestoreNow" => false
                                    ]
                                ]
                            )
                        );

                }

            }

        } else {

            die(self::generateJson(["response" => ["error" => "token not found"]]));

        }

    }

    /**
     * @throws MySqlException
     */
    public static function checkAdmin(string $token) {

        if (Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE id = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] == 1) {

            self::checkToken($token);

            if (Database::getInstance()->query("SELECT isAdmin FROM eviger.eviger_users WHERE login = '?s'", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->fetchAssoc()['isAdmin'] == 1) {

                return true;

            } else {

                return false;

            }

        } else {

            http_response_code(404);

        }

    }
}