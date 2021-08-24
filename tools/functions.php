<?php

require_once "vendor/autoload.php";

use Krugozor\Database\Mysql;

$db = Mysql::create("localhost", "user", "password")->setDatabaseName("eviger")->setCharset("utf8mb4");

function sendJson($array) {
    
    return json_encode($array, JSON_UNESCAPED_UNICODE);
    
}

function encryptMessage($text) {
    
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($text, $cipher, "SECRET_KEY", $options = OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, "SECRET_KEY", $as_binary = true);
    return base64_encode($iv.$hmac.$ciphertext_raw);
    
}

function decryptMessage($text_encrypted) {
    
    $c = base64_decode($text_encrypted);
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $sha2len=32);
    $text_encrypted_raw = substr($c, $ivlen+$sha2len);
    return openssl_decrypt($text_encrypted_raw, $cipher, "SECRET_KEY", $options = OPENSSL_RAW_DATA, $iv);
    
}

function checkToken($token) {
    
    global $db;
    
    if (isset($token)) {
        
        if ($db->query("SELECT * FROM eviger_tokens WHERE token = '?s'", $token)->getNumRows()) {
            
            if ($db->query("SELECT * FROM eviger_deactivated_accounts WHERE eid = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid'])->getNumRows()) die(sendJson(["response" => ["error" => "account deactivated", "canRestoreNow" => true]]));

            $bans = $db->query("SELECT * FROM eviger_bans WHERE eid = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['eid']);
            
            if ($bans->getNumRows()) {
            
                switch ($bans->fetchAssoc()['type']) {
                    
                    case 1:
                        die(
                            sendJson(
                                ["response" => 
                                    ["error" => "account banned",
                                     "details" => ["reason" => $bans->fetchAssoc()['reason']],
                                     "canRestoreNow" => $bans->fetchAssoc()['time_unban'] > time()
                                    ]
                                ]
                            )
                        );
                    break;
                    case 2:
                        die(
                            sendJson(
                                ["response" => 
                                    ["error" => "account banned",
                                     "details" => ["reason" => $bans->fetchAssoc()['reason']],
                                     "canRestoreNow" => false
                                    ]
                                ]
                            )
                        );
                    break;
                    
                }
                /*
                    die(
                        sendJson(
                            ["response" => 
                                ["error" => "token inactive due hacking", 
                                 "canRestore" => 
                                ]
                            ]
                        )
                    )
                    :
                    die(
                        sendJson(
                            ["response" => 
                                ["error" => "token inactive due delete profile at own request", 
                                 "canRestore" => true
                                ]
                            ]
                        )
                    );
                */
            }
            
        } else {
            
            die(sendJson(["response" => ["error" => "token not found"]]));
            
        }
        
    } else {
        
        die(sendJson(["response" => ["error" => "token not setted"]]));
        
    }
    
}

function checkAdmin($token) {
    
    global $db;
    
    if ($db->query("SELECT isAdmin FROM eviger_users WHERE login = '?s'", $db->query("SELECT login FROM eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['login'])->fetchAssoc()['isAdmin'] == 1) {
        
        checkToken($token);
        
        if ($db->query("SELECT isAdmin FROM eviger_users WHERE login = '?s'", $db->query("SELECT login FROM eviger_tokens WHERE token = '?s'", $token)->fetchAssoc()['login'])->fetchAssoc()['isAdmin'] == 1) {
            
            return true;
            
        } else {
            
            return false;
            
        }
        
    } else {
        
        http_response_code(404);
        
    }
    
}