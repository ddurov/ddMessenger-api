<?php

header('Access-Control-Allow-Origin: *');

require_once "../tools/vendor/autoload.php";

use Eviger\Api\Methods\Email;
use Eviger\Api\Methods\Messages;
use Eviger\Api\Methods\Service;
use Eviger\Api\Methods\User;
use Eviger\Api\Methods\Users;
use Eviger\Api\Tools\Other;
use Eviger\Database;
use Eviger\Mail;

preg_match("~/methods/(.*)~", $_SERVER['REQUEST_URI'], $matches);

if (count($matches) === 0) {
    echo time();
    return;
}

$method = explode("?", $matches[1])[0];

if (!isset($method)) die(Other::generateJson(["response" => ["error" => "method not setted"]]));

$mixedData = $_SERVER['REQUEST_METHOD'] == "GET" ? $_GET : json_decode(file_get_contents('php://input'), true);

if (!isset($mixedData['method'])) die(Other::generateJson(["response" => ["error" => "sub-method not setted"]]));

if (!in_array($mixedData['method'], ["getUpdates", "auth", "registerAccount", "restorePassword", "createCode", "confirmCode"])) {
    if (!isset($mixedData['token'])) die(Other::generateJson(["response" => ["error" => "token not setted"]]));
    $checkToken = Other::checkToken($mixedData['token']);
    if (!$checkToken) die($checkToken);
}

switch ($method) {

    case "email":
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            switch ($mixedData['method']) {

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        } else {
            switch ($mixedData['method']) {

                case 'createCode':
                    if (!isset($mixedData['email'])) die(Other::generateJson(["response" => ["error" => "email not setted"]]));

                    die(Email::createCode($mixedData['email'], Mail::getInstance()));

                case 'confirmCode':
                    if (!isset($mixedData['email'])) die(Other::generateJson(["response" => ["error" => "email not setted"]]));

                    if (!isset($mixedData['code'])) die(Other::generateJson(["response" => ["error" => "code not setted"]]));

                    if (!isset($mixedData['hash'])) die(Other::generateJson(["response" => ["error" => "hash not setted"]]));

                    die(Email::confirmCode($mixedData['email'], $mixedData['code'], $mixedData['hash']));

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        }

    case "service":
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            switch ($mixedData['method']) {

                case 'getUpdates':
                    die(Service::getUpdates());

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        } else {
            switch ($mixedData['method']) {

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        }

    case "messages":
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            switch ($mixedData['method']) {

                case 'getDialogs':
                    die(Messages::getDialogs($mixedData['token']));

                case 'getHistory':
                    if (!isset($mixedData['id'])) die(Other::generateJson(["response" => ["error" => "id not setted"]]));

                    die(Messages::getHistory($mixedData['id'], $mixedData['token']));

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        } else {
            switch ($mixedData['method']) {
                case 'send':
                    if (!isset($mixedData['to_id'])) die(Other::generateJson(["response" => ["error" => "to_id not setted"]]));

                    if (!isset($mixedData['text'])) die(Other::generateJson(["response" => ["error" => "text not setted"]]));

                    die(Messages::send($mixedData['to_id'], $mixedData['text'], $mixedData['token']));

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        }
    case "user":
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            switch ($mixedData['method']) {

                case 'auth':
                    // login checks

                    if (!isset($mixedData['login'])) die(Other::generateJson(["response" => ["error" => "login not setted"]]));

                    if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) die(Other::generateJson(["response" => ["error" => "the login is too big or too small"]]));

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) die(Other::generateJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]));

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "user not found"]]));

                    // password checks

                    if (!isset($mixedData['password'])) die(Other::generateJson(["response" => ["error" => "password not setted"]]));

                    if ((mb_strlen($mixedData['password']) <= 8 || $mixedData['password'] >= 60)) die(Other::generateJson(["response" => ["error" => "the password is too big or too small"]]));

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) die(Other::generateJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]));

                    // processing authentication

                    die(User::auth($mixedData['login'], $mixedData['password']));

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

            }
        } else {
            switch ($mixedData['method']) {
                case 'registerAccount':
                    // login checks

                    if (!isset($mixedData['login'])) die(Other::generateJson(["response" => ["error" => "login not setted"]]));

                    if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) die(Other::generateJson(["response" => ["error" => "the login is too big or too small"]]));

                    if (!preg_match("/[a-zA-Z0-9_]/gu", $mixedData['login'])) die(Other::generateJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]));

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "the user is already registered"]]));

                    // password checks

                    if (!isset($mixedData['password'])) die(Other::generateJson(["response" => ["error" => "password not setted"]]));

                    if ((mb_strlen($mixedData['password']) <= 8 || $mixedData['password'] >= 64)) die(Other::generateJson(["response" => ["error" => "the password is too big or too small"]]));

                    if (!preg_match("/[a-zA-Z0-9_]/gu", $mixedData['password'])) die(Other::generateJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]));

                    // email checks

                    if (!isset($mixedData['email'])) die(Other::generateJson(["response" => ["error" => "email not setted"]]));

                    if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email'])) die(Other::generateJson(["response" => ["error" => "email incorrect"]]));

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "email is busy"]]));

                    // username checks

                    if ($mixedData['userName'] !== null && isset($mixedData['userName'])) {

                        if (mb_strlen($mixedData['userName']) < 6 || mb_strlen($mixedData['userName']) > 128) die(Other::generateJson(["response" => ["error" => "username must be more than 6 or less than 128 characters"]]));

                        if (preg_match("/^e?id+[\d]+/gu", $mixedData['userName'])) die(Other::generateJson(["response" => ["error" => "username cannot contain the prefix eid or id"]]));

                        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "username is busy"]]));

                    }

                    // processing registering account or email confirmation

                    if (isset($mixedData['registrationEmailCode'])) {

                        die(User::registerAccount($mixedData['login'], $mixedData['password'], $mixedData['email'], ($mixedData['userName'] === null) ? null : $mixedData['userName'], $mixedData['registrationEmailCode'], $mixedData['hashCode']));

                    } else {

                        die(Other::generateJson(["response" => ["error" => "registrationEmailCode not setted"]]));

                    }

                case 'restorePassword':
                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s' AND email = '?s'", $mixedData['login'], $mixedData['email'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "login/email pair is not correct"]]));

                    if (!isset($mixedData['confirmCode'])) {

                        $getCode = json_decode(Email::createCode($mixedData['email'], Mail::getInstance()), true);

                        if ($getCode['response']['status'] !== "ok") die(Other::generateJson(["response" => ["error" => $getCode['response']['error']]]));

                        die(Other::generateJson(["response" => ["status" => "confirm your email", "hash" => $getCode['response']["hash"]]]));

                    } else {

                        $confirmCode = json_decode(Email::confirmCode($mixedData['email'], $mixedData['confirmCode'], $mixedData['hash']));

                        if ($confirmCode['response'] !== true) die(Other::generateJson(["response" => ["error" => $confirmCode['response']['error']]]));

                        die(User::restorePassword($mixedData['email'], $mixedData['newPassword']));

                    }

                case 'setOnline':
                    die(User::setOnline($mixedData['token']));

                case 'setOffline':
                    die(User::setOffline($mixedData['token']));

                case 'changeName':
                    // newName checks

                    if (!isset($mixedData['newName'])) die(Other::generateJson(["response" => ["error" => "newName not setted"]]));

                    if (mb_strlen($mixedData['newName']) < 6 || mb_strlen($mixedData['newName']) > 128) die(Other::generateJson(["response" => ["error" => "newName must be more than 6 or less than 128 characters"]]));

                    if (preg_match("/^(e)?id.*/gu", $mixedData['newName'])) die(Other::generateJson(["response" => ["error" => "newName cannot contain the prefix eid or id"]]));

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "username is busy"]]));

                    // email checks

                    if (!isset($mixedData['email'])) die(Other::generateJson(["response" => ["error" => "email not setted"]]));

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "email not found"]]));

                    // code and hashCode checks

                    if (!isset($mixedData['code']) || !isset($mixedData['hashCode'])) die(Other::generateJson(["response" => ["error" => "hashCode or code not setted"]]));

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE code = '?s' AND hash = '?s'", $mixedData['code'], $mixedData['hashCode'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "code or hash invalid"]]));

                    die(User::changeName($mixedData['name'], $mixedData['email'], $mixedData['code'], $mixedData['hashCode']));

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData === null ? [] : $mixedData]]));
            }
        }
    case "users":
        switch ($mixedData['method']) {

            case 'get':
                if (!isset($mixedData['id'])) die(Users::get($mixedData['token']));

                die(Users::get($mixedData['token']));

            case 'search':
                if (isset($mixedData['query']) && $mixedData['query'] !== "") {

                    die(Users::search($mixedData['query']));

                } else {

                    die(Other::generateJson(["response" => []]));

                }

            default:
                die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));

        }

    default:
        die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $mixedData]]));

}