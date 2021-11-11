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

preg_match("~/methods/(.*)~", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches);

if (count($matches) === 0) {
    echo time();
    return;
} else {
    header('Content-Type: application/json; charset=utf-8');
}

$method = $matches[1];
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
                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"]);
                    die(Email::createCode($mixedData['email']));

                case "confirmCode":
                    Other::postUsageMethod();
                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"]);
                    if (!isset($mixedData['code']) || $mixedData['code'] === "") throw new selfThrows(["message" => "code parameter is missing or null"]);
                    if (!isset($mixedData['hash']) || $mixedData['hash'] === "") throw new selfThrows(["message" => "hash parameter is missing or null"]);
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
                    if (!isset($mixedData['id']) || $mixedData['id'] === "") throw new selfThrows(["message" => "id parameter is missing or null"]);
                    die(Messages::getHistory($mixedData['id'], $mixedData['token']));

                case "send":
                    Other::postUsageMethod();
                    if (!isset($mixedData['to_id']) || $mixedData['to_id'] === "") throw new selfThrows(["message" => "to_id parameter is missing or null"]);
                    if (!isset($mixedData['text']) || $mixedData['text'] === "") throw new selfThrows(["message" => "text parameter is missing or null"]);
                    die(Messages::send($mixedData['to_id'], $mixedData['text'], $mixedData['token']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }

        case "user":

            switch ($mixedData['method']) {

                case "auth":
                    // login checks

                    if (!isset($mixedData['login']) || $mixedData['login'] === "") throw new selfThrows(["message" => "login parameter is missing or null"]);

                    if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) throw new selfThrows(["message" => "the login is too big or too small"]);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) throw new selfThrows(["message" => "the login must contain a-z, A-Z, 0-9 and _"]);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) throw new selfThrows(["message" => "user not found"]);

                    // password checks

                    if (!isset($mixedData['password']) || $mixedData['password'] === "") throw new selfThrows(["message" => "password parameter is missing or null"]);

                    if ((mb_strlen($mixedData['password']) <= 8 || $mixedData['password'] >= 64)) throw new selfThrows(["message" => "the password is too big or too small"]);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) throw new selfThrows(["message" => "the password must contain a-z, A-Z, 0-9 and _"]);

                    // processing authentication

                    die(User::auth($mixedData['login'], $mixedData['password']));

                case 'registerAccount':
                    Other::postUsageMethod();

                    // login checks

                    if (!isset($mixedData['login']) || $mixedData['login'] === "") throw new selfThrows(["message" => "login parameter is missing"]);

                    if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) throw new selfThrows(["message" => "the login is too big or too small"]);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) throw new selfThrows(["message" => "the login must contain a-z, A-Z, 0-9 and _"]);

                    // password checks

                    if (!isset($mixedData['password']) || $mixedData['password'] === "") throw new selfThrows(["message" => "password parameter is missing or null"]);

                    if ((mb_strlen($mixedData['password']) <= 8 || $mixedData['password'] >= 64)) throw new selfThrows(["message" => "the password is too big or too small"]);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) throw new selfThrows(["message" => "the password must contain a-z, A-Z, 0-9 and _"]);

                    // email checks

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"]);

                    if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email'])) throw new selfThrows(["message" => "email invalid"]);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "email is busy"]);

                    // username checks

                    if ($mixedData['userName'] !== null && isset($mixedData['userName'])) {

                        if (mb_strlen($mixedData['userName']) < 6 || mb_strlen($mixedData['userName']) > 128) throw new selfThrows(["message" => "username must be more than 6 or less than 128 characters"]);

                        if (preg_match("/^e?id+[\d]+/u", $mixedData['userName'])) throw new selfThrows(["message" => "username cannot contain the prefix eid or id"]);

                        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) throw new selfThrows(["message" => "username is busy"]);

                    }

                    // processing registering account

                    if (!isset($mixedData['registrationEmailCode']) || $mixedData['registrationEmailCode'] === "") throw new selfThrows(["message" => "registrationEmailCode parameter is missing or null"]);

                    if (!isset($mixedData['hashCode']) || $mixedData['hashCode'] === "") throw new selfThrows(["message" => "hashCode parameter is missing or null"]);

                    die(User::registerAccount($mixedData['login'], $mixedData['password'], $mixedData['email'], ($mixedData['userName'] === null) ? null : $mixedData['userName'], $mixedData['registrationEmailCode'], $mixedData['hashCode']));

                case 'restorePassword':
                    Other::postUsageMethod();

                    if (!isset($mixedData['login']) || $mixedData['login'] === "") throw new selfThrows(["message" => "login parameter is missing or null"]);

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"]);

                    if (!isset($mixedData['emailCode']) && !isset($mixedData['hashCode'])) {

                        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s' AND email = '?s'", $mixedData['login'], $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "login/email pair is not correct"]);

                        (new Response)->setStatus("ok")->setResponse(["message" => "confirm email"])->send();

                    }

                    $confirmCode = json_decode(Email::confirmCode($mixedData['email'], $mixedData['emailCode'], $mixedData['hashCode']), true);

                    if ($confirmCode['response'] !== true) throw new selfThrows(["message" => $confirmCode['response']['message']]);

                    die(User::restorePassword($mixedData['email'], $mixedData['newPassword']));

                case 'setOnline':
                    Other::postUsageMethod();
                    die(User::setOnline($mixedData['token']));

                case 'setOffline':
                    Other::postUsageMethod();
                    die(User::setOffline($mixedData['token']));

                case 'changeName':
                    Other::postUsageMethod();
                    // newName checks

                    if (!isset($mixedData['newName']) || $mixedData['newName'] === "") throw new selfThrows(["message" => "newName parameter is missing or null"]);

                    if (mb_strlen($mixedData['newName']) < 6 || mb_strlen($mixedData['newName']) > 128) throw new selfThrows(["message" => "newName must be more than 6 or less than 128 characters"]);

                    if (preg_match("/^(e)?id.*/gu", $mixedData['newName'])) throw new selfThrows(["message" => "newName cannot contain the prefix eid or id"]);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) throw new selfThrows(["message" => "newName is busy"]);

                    // email checks

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"]);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "email not found"]);

                    // code and hashCode checks

                    if (!isset($mixedData['emailCode']) || $mixedData['emailCode'] === "") throw new selfThrows(["message" => "emailCode parameter is missing or null"]);

                    if (!isset($mixedData['hashCode']) || $mixedData['hashCode'] === "") throw new selfThrows(["message" => "hashCode parameter is missing or null"]);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE code = '?s' AND hash = '?s'", $mixedData['emailCode'], $mixedData['hashCode'])->getNumRows()) throw new selfThrows(["message" => "emailCode or hashCode invalid"]);

                    die(User::changeName($mixedData['name'], $mixedData['email'], $mixedData['code'], $mixedData['hashCode']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData]);

            }

        case "users":

            switch ($mixedData['method']) {

                case 'get':
                    if (!isset($mixedData['id'])) die(Users::get($mixedData['token']));

                    die(Users::get($mixedData['token'], $mixedData['id']));

                case 'search':
                    if (isset($mixedData['query']) && $mixedData['query'] !== "") die(Users::search($mixedData['query']));

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

    Other::log("Error: " . $exceptions->getMessage() . " on line: " . $exceptions->getLine() . " in: " . $exceptions->getFile());
    (new Response)->setStatus("error")->setResponse(["message" => "internal error, try later"])->send();

}