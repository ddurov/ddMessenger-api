<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');

require_once "../filesEviger/vendor/autoload.php";

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

$mixedData = $_SERVER['REQUEST_METHOD'] === "GET" ? $_GET : json_decode(file_get_contents('php://input'), true);

try {

    $method = $matches[1];

    $subMethod = $mixedData['method'];

    if (!isset($method) || $method === "") throw new selfThrows(["message" => "method parameter is missing or null"]);

    if (!isset($subMethod) || $subMethod === "") throw new selfThrows(["message" => "sub-method parameter is missing or null"]);

    switch ($method) {

        case "email":

            switch ($subMethod) {

                case "createCode":
                    Other::postUsageMethod();

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"], 400);

                    die(Email::createCode($mixedData['email']));

                case "confirmCode":
                    Other::postUsageMethod();

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"], 400);

                    if (!isset($mixedData['emailCode']) || $mixedData['emailCode'] === "") throw new selfThrows(["message" => "emailCode parameter is missing or null"], 400);

                    if (!isset($mixedData['hashCode']) || $mixedData['hashCode'] === "") throw new selfThrows(["message" => "hashCode parameter is missing or null"], 400);

                    die(Email::confirmCode($mixedData['email'], $mixedData['emailCode'], $mixedData['hashCode']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData], 400);

            }

        case "service":

            switch ($subMethod) {

                case "getUpdates":
                    die(Service::getUpdates());

                case "getPinningHashByDomain":
                    if (!isset($mixedData['domain']) || $mixedData['domain'] === "") throw new selfThrows(["message" => "domain parameter is missing or null"], 400);

                    die(Service::getPinningHashByDomain($mixedData['domain']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData], 400);

            }

        case "messages":

            switch ($subMethod) {

                case "getDialogs":
                    Other::checkToken($mixedData['token']);

                    die(Messages::getDialogs($mixedData['token']));

                case "getHistory":
                    Other::checkToken($mixedData['token']);

                    if (!isset($mixedData['id']) || $mixedData['id'] === "") throw new selfThrows(["message" => "id parameter is missing or null"], 400);

                    die(Messages::getHistory($mixedData['id'], $mixedData['token']));

                case "send":
                    Other::postUsageMethod();
                    Other::checkToken($mixedData['token']);

                    if (!isset($mixedData['toId']) || $mixedData['toId'] === "") throw new selfThrows(["message" => "toId parameter is missing or null"], 400);

                    if (!isset($mixedData['text']) || $mixedData['text'] === "") throw new selfThrows(["message" => "text parameter is missing or null"], 400);

                    die(Messages::send($mixedData['toId'], $mixedData['text'], $mixedData['token']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData], 400);

            }

        case "user":

            switch ($subMethod) {

                case "auth":
                    if (!isset($mixedData['login']) || $mixedData['login'] === "") throw new selfThrows(["message" => "login parameter is missing or null"], 400);

                    if (!isset($mixedData['password']) || $mixedData['password'] === "") throw new selfThrows(["message" => "password parameter is missing or null"], 400);

                    if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) throw new selfThrows(["message" => "login is too big or too small"], 400);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) throw new selfThrows(["message" => "login must contain a-z, A-Z, 0-9 and _"], 400);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) throw new selfThrows(["message" => "user not found"], 404);

                    if (mb_strlen($mixedData['password']) <= 8) throw new selfThrows(["message" => "password is too small"], 400);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) throw new selfThrows(["message" => "password must contain a-z, A-Z, 0-9 and _"], 400);

                    die(User::auth($mixedData['login'], $mixedData['password']));

                case 'registerAccount':
                    Other::postUsageMethod();

                    if (!isset($mixedData['login']) || $mixedData['login'] === "") throw new selfThrows(["message" => "login parameter is missing or null"], 400);

                    if (!isset($mixedData['password']) || $mixedData['password'] === "") throw new selfThrows(["message" => "password parameter is missing or null"], 400);

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"], 400);

                    if (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20) throw new selfThrows(["message" => "login is too big or too small"], 400);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) throw new selfThrows(["message" => "login must contain a-z, A-Z, 0-9 and _"], 400);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) throw new selfThrows(["message" => "user with provided login already registered"], 409);

                    if (mb_strlen($mixedData['password']) <= 8) throw new selfThrows(["message" => "password is too small"], 400);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) throw new selfThrows(["message" => "password must contain a-z, A-Z, 0-9 and _"], 400);

                    if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email'])) throw new selfThrows(["message" => "email invalid"], 400);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "user with provided email already registered"], 409);

                    if (isset($mixedData['userName']) && $mixedData['userName'] !== "") {

                        if (mb_strlen($mixedData['userName']) < 6 || mb_strlen($mixedData['userName']) > 128) throw new selfThrows(["message" => "username must be more than 6 or less than 128 characters"], 400);

                        if (preg_match("/^e?id+[\d]+/", $mixedData['userName'])) throw new selfThrows(["message" => "username cannot contain the prefix eid or id"], 400);

                        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) throw new selfThrows(["message" => "user with provided username already registered"], 409);

                    }

                    if ((!isset($mixedData['emailCode']) || $mixedData['emailCode'] === "") && (!isset($mixedData['hashCode']) || $mixedData['hashCode'] === "")) {

                        (new Response)->setCode(100)->setStatus("ok")->setResponse(["message" => "confirm email"])->send();

                    }

                    $confirmCode = json_decode(Email::confirmCode($mixedData['email'], $mixedData['emailCode'], $mixedData['hashCode']), true);

                    if ($confirmCode['status'] !== "ok") throw new selfThrows(["message" => $confirmCode['response']['message']], http_response_code());

                    die(User::registerAccount($mixedData['login'], $mixedData['password'], $mixedData['email'], $mixedData['userName'], $mixedData['emailCode'], $mixedData['hashCode']));

                case 'resetPassword':
                    Other::postUsageMethod();

                    if (!isset($mixedData['login']) || $mixedData['login'] === "") throw new selfThrows(["message" => "login parameter is missing or null"], 400);

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"], 400);

                    if (!isset($mixedData['newPassword']) || $mixedData['newPassword'] === "") throw new selfThrows(["message" => "newPassword parameter is missing or null"], 400);

                    if (mb_strlen($mixedData['newPassword']) <= 8) throw new selfThrows(["message" => "newPassword is too small"], 400);

                    if (!preg_match("/[a-zA-Z0-9_]/ui", $mixedData['newPassword'])) throw new selfThrows(["message" => "newPassword must contain a-z, A-Z, 0-9 and _"], 400);

                    if ((!isset($mixedData['emailCode']) || $mixedData['emailCode'] === "") && (!isset($mixedData['hashCode']) || $mixedData['hashCode'] === "")) {

                        if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s' AND email = '?s'", $mixedData['login'], $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "login/email pair is not correct"], 400);

                        (new Response)->setCode(100)->setStatus("ok")->setResponse(["message" => "confirm email"])->send();

                    }

                    $confirmCode = json_decode(Email::confirmCode($mixedData['email'], $mixedData['emailCode'], $mixedData['hashCode']), true);

                    if ($confirmCode['response'] !== true) throw new selfThrows(["message" => $confirmCode['response']['message']], http_response_code());

                    die(User::resetPassword($mixedData['email'], $mixedData['newPassword']));

                case 'setOnline':
                    Other::postUsageMethod();
                    Other::checkToken($mixedData['token']);

                    die(User::setOnline($mixedData['token']));

                case 'changeName':
                    Other::postUsageMethod();

                    if (!isset($mixedData['newName']) || $mixedData['newName'] === "") throw new selfThrows(["message" => "newName parameter is missing or null"], 400);

                    if (!isset($mixedData['email']) || $mixedData['email'] === "") throw new selfThrows(["message" => "email parameter is missing or null"], 400);

                    if (mb_strlen($mixedData['newName']) <= 6 || mb_strlen($mixedData['newName']) >= 128) throw new selfThrows(["message" => "newName must be more than 6 or less than 128 characters"], 400);

                    if (preg_match("/^e?id+[\d]+/", $mixedData['newName'])) throw new selfThrows(["message" => "newName cannot contain the prefix eid or id"], 400);

                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['newName'])->getNumRows()) throw new selfThrows(["message" => "user with provided newName already registered"], 409);

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) throw new selfThrows(["message" => "email not found"], 404);

                    if ((!isset($mixedData['emailCode']) || $mixedData['emailCode'] === "") && (!isset($mixedData['hashCode']) || $mixedData['hashCode'] === "")) {

                        (new Response)->setCode(100)->setStatus("ok")->setResponse(["message" => "confirm email"])->send();

                    }

                    if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE code = '?s' AND hash = '?s'", $mixedData['emailCode'], $mixedData['hashCode'])->getNumRows()) throw new selfThrows(["message" => "emailCode or hashCode invalid"], 400);

                    die(User::changeName($mixedData['newName'], $mixedData['email'], $mixedData['emailCode'], $mixedData['hashCode']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData], 400);

            }

        case "users":

            switch ($subMethod) {

                case 'get':
                    Other::checkToken($mixedData['token']);

                    (!isset($mixedData['id'])) ? die(Users::get(0, $mixedData['token'])) : die(Users::get($mixedData['id'], $mixedData['token']));

                case 'search':
                    Other::checkToken($mixedData['token']);

                    if (!isset($mixedData['query']) || $mixedData['query'] === "") throw new selfThrows(["message" => "query parameter is missing or null"], 400);

                    die(Users::search($mixedData['query'], $mixedData['token']));

                default:
                    throw new selfThrows(["message" => "unknown sub-method", "parameters" => $mixedData], 400);

            }

        default:
            throw new selfThrows(["message" => "unknown method", "parameters" => $mixedData], 400);

    }

} catch (selfThrows $e) {

    die($e->getMessage());

} catch (Throwable $exceptions) {

    Other::log("Error: " . $exceptions->getMessage() . " on line: " . $exceptions->getLine() . " in: " . $exceptions->getFile());
    (new Response)->setCode(500)->setStatus("error")->setResponse(["message" => "internal error, try later"])->send();

}