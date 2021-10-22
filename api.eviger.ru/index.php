<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once "../vendor/autoload.php";

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
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
$mixedData = $_SERVER['REQUEST_METHOD'] == "GET" ? $_GET : json_decode(file_get_contents('php://input'), true);

try {

    if (!isset($method)) throw new selfThrows(["message" => "method parameter is missing"]);

    if (!isset($mixedData['method'])) throw new selfThrows(["message" => "sub-method parameter is missing"]);

    if (!in_array($mixedData['method'], ["getUpdates", "auth", "registerAccount", "restorePassword", "createCode", "confirmCode"])) {
        if (!isset($mixedData['token'])) throw new selfThrows(["message" => "token parameter is missing"]);
        Other::checkToken($mixedData['token']);
    }

    switch ($method) {

        case "email":

            switch ($mixedData['method']) {

                case "createCode":
                    Other::postUsageMethod();
                    if (!isset($mixedData['email'])) throw new selfThrows(["message" => "email parameter is missing"]);
                    die(Email::createCode($mixedData['email'], Mail::getInstance()));

                case "confirmCode":
                    Other::postUsageMethod();
                    if (!isset($mixedData['email'])) throw new selfThrows(["message" => "email parameter is missing"]);
                    if (!isset($mixedData['code'])) throw new selfThrows(["message" => "code parameter is missing"]);
                    if (!isset($mixedData['email'])) throw new selfThrows(["message" => "hash parameter is missing"]);
                    die(Email::confirmCode($mixedData['email'], $mixedData['code'], $mixedData['hash']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }

        case "service":

            switch ($mixedData['method']) {

                case "getUpdates":
                    die(Service::getUpdates());

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }

        case "messages":

            switch ($mixedData['method']) {

                case "getDialogs":
                    die(Messages::getDialogs($mixedData['token']));

                case "getHistory":
                    if (!isset($mixedData['id'])) throw new selfThrows(["message" => "id parameter is missing"]);
                    die(Messages::getHistory($mixedData['id'], $mixedData['token']));

                case "send":
                    Other::postUsageMethod();
                    if (!isset($mixedData['to_id'])) throw new selfThrows(["message" => "to_id parameter is missing"]);
                    if (!isset($mixedData['text'])) throw new selfThrows(["message" => "text parameter is missing"]);
                    die(Messages::send($mixedData['to_id'], $mixedData['text'], $mixedData['token']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }

        case "user":

            switch ($mixedData['method']) {

                case "auth":
                    Other::local_checkLoginAndPassword($mixedData);

                    // processing authentication

                    die(User::auth($mixedData['login'], $mixedData['password']));

                case 'registerAccount':
                    Other::postUsageMethod();
                    Other::local_checkLoginAndPassword($mixedData);

                    // email checks

                    if (!isset($mixedData['email'])) throw new selfThrows(["message" => "email parameter is missing"]);

                    if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email'])) throw new selfThrows(["message" => "email invalid"]);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "email is busy"]);

                    // username checks

                    if ($mixedData['userName'] !== null && isset($mixedData['userName'])) {

                        if (mb_strlen($mixedData['userName']) < 6 || mb_strlen($mixedData['userName']) > 128) throw new selfThrows(["message" => "username must be more than 6 or less than 128 characters"]);

                        if (preg_match("/^e?id+[\d]+/u", $mixedData['userName'])) throw new selfThrows(["message" => "username cannot contain the prefix eid or id"]);

                        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) throw new selfThrows(["message" => "username is busy"]);

                    }

                    // processing registering account or email confirmation

                    if (isset($mixedData['registrationEmailCode'])) {

                        die(User::registerAccount($mixedData['login'], $mixedData['password'], $mixedData['email'], ($mixedData['userName'] === null) ? null : $mixedData['userName'], $mixedData['registrationEmailCode'], $mixedData['hashCode']));

                    } else {

                        throw new selfThrows(["message" => "registrationEmailCode parameter is missing"]);

                    }

                case 'restorePassword':
                    Other::postUsageMethod();
                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s' AND email = '?s'", $mixedData['login'], $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "login/email pair is not correct"]);

                    if (!isset($mixedData['confirmCode'])) {

                        $getCode = json_decode(Email::createCode($mixedData['email'], Mail::getInstance()), true);

                        if ($getCode['response']['status'] !== "ok") throw new selfThrows(["message" => $getCode['response']['message']]);

                        (new Response)
                            ->setStatus("confirm email")
                            ->setResponse(["hash" => $getCode['response']["hash"]])
                            ->send();

                    } else {

                        $confirmCode = json_decode(Email::confirmCode($mixedData['email'], $mixedData['confirmCode'], $mixedData['hash']));

                        if ($confirmCode['response'] !== true) throw new selfThrows(["message" => $confirmCode['response']['message']]);

                        die(User::restorePassword($mixedData['email'], $mixedData['newPassword']));

                    }
                    break;

                case 'setOnline':
                    Other::postUsageMethod();
                    die(User::setOnline($mixedData['token']));

                case 'setOffline':
                    Other::postUsageMethod();
                    die(User::setOffline($mixedData['token']));

                case 'changeName':
                    Other::postUsageMethod();
                    // newName checks

                    if (!isset($mixedData['newName'])) throw new selfThrows(["message" => "newName parameter is missing"]);

                    if (mb_strlen($mixedData['newName']) < 6 || mb_strlen($mixedData['newName']) > 128) throw new selfThrows(["message" => "newName must be more than 6 or less than 128 characters"]);

                    if (preg_match("/^(e)?id.*/gu", $mixedData['newName'])) throw new selfThrows(["message" => "newName cannot contain the prefix eid or id"]);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) throw new selfThrows(["message" => "newName is busy"]);

                    // email checks

                    if (!isset($mixedData['email'])) throw new selfThrows(["message" => "email parameter is missing"]);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "email not found"]);

                    // code and hashCode checks

                    if (!isset($mixedData['code']) || !isset($mixedData['hashCode'])) throw new selfThrows(["message" => "hashCode or code parameter is missing"]);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE code = '?s' AND hash = '?s'", $mixedData['code'], $mixedData['hashCode'])->getNumRows()) throw new selfThrows(["message" => "code or hashCode invalid"]);

                    die(User::changeName($mixedData['name'], $mixedData['email'], $mixedData['code'], $mixedData['hashCode']));


                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }
            break;

        case "users":

            switch ($mixedData['method']) {

                case 'get':

                    if (!isset($mixedData['id'])) {
                        die(Users::get($mixedData['token']));
                    }

                    die(Users::get($mixedData['token'], $mixedData['id']));

                case 'search':
                    if (isset($mixedData['query']) && $mixedData['query'] !== "") {
                        die(Users::search($mixedData['query']));
                    }

                    (new Response)
                        ->setStatus("ok")
                        ->setResponse([])
                        ->send();
                    break;

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }
            break;

        default:
            throw new selfThrows(["message" => "unknown method", "parameters" => $mixedData]);

    }

} catch (selfThrows $e) {

    die($e->getMessage());

} catch (Throwable $exceptions) {

    Other::log($exceptions->getMessage());
    (new Response)->setStatus("error")->setResponse(["message" => "internal error, try later"])->send();

}