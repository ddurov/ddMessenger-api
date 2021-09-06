<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Email;
use Eviger\Api\Methods\User;
use Eviger\Api\Tools\Other;
use Eviger\Database;
use Eviger\Mail;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$data = json_decode(file_get_contents('php://input'), true);

    $tokenStatus = Other::checkToken($data['token']);

    if (!in_array($data['method'], ["registerAccount", "restorePassword"])) $tokenStatus == true or die($tokenStatus);

	switch ($data['method']) {

		case 'registerAccount':

			if (isset($data['login']) && (mb_strlen($data['login']) > 6 && mb_strlen($data['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $data['login']) && !Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $data['login'])->getNumRows()) {

				if (isset($data['password']) && (mb_strlen($data['password']) > 8 && mb_strlen($data['password']) < 60) && preg_match("/[a-zA-Z0-9_]/ui", $data['password'])) {

					if (isset($data['email']) && preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email']) && !Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $data['email'])->getNumRows()) {

                        if ($data['userName'] !== null && isset($data['userName'])) {

                            if (preg_match("/^(e)?id.*/gu", $data['userName'])) die(Other::generateJson(["response" => ["error" => "username cannot contain the prefix eid or id"]]));

                            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $data['userName'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "username is busy"]]));

                        }

						if (isset($data['registrationEmailCode'])) {

                            die(User::registerAccount($data['login'], $data['password'], $data['email'], ($data['userName'] === null) ? null : $data['userName'], $data['registrationEmailCode'], $data['hashCode']));

						} else {

                            $getCode = json_decode(Email::createCode($data['email'], Mail::getInstance()), true);

                            if (isset($getCode['response']['error'])) {
                                die(Other::generateJson(["response" => ["error" => $getCode['response']['error']]]));
                            } else {
                                die(Other::generateJson(["response" => ["status" => "confirm your email", "hash" => $getCode['response']["hash"]]]));
                            }

						}

					} elseif (!isset($data['email'])) {

                        die(Other::generateJson(["response" => ["error" => "email not setted"]]));

					} elseif (isset($data['email']) && !preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email'])) {

                        die(Other::generateJson(["response" => ["error" => "email incorrect"]]));

					} elseif (isset($data['email']) && preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email']) && Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $data['email'])->getNumRows()) {

                        die(Other::generateJson(["response" => ["error" => "email is busy"]]));

					}

				} elseif (!isset($data['password'])) {

                    die(Other::generateJson(["response" => ["error" => "password not setted"]]));

				} elseif (isset($data['password']) && (mb_strlen($data['password']) < 8 || mb_strlen($data['password']) > 60)) {

                    die(Other::generateJson(["response" => ["error" => "the password is too big or too small"]]));

				} elseif (isset($data['password']) && (mb_strlen($data['password']) > 8 && mb_strlen($data['password']) < 60) && !preg_match("/[a-zA-Z0-9_]/ui", $data['password'])) {

                    die(Other::generateJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]));

				}

			} elseif (!isset($data['login'])) {

                die(Other::generateJson(["response" => ["error" => "login not setted"]]));

			} elseif (isset($data['login']) && (mb_strlen($data['login']) <= 6 || mb_strlen($data['login']) >= 20)) {

                die(Other::generateJson(["response" => ["error" => "the login is too big or too small"]]));

			} elseif (isset($data['login']) && (mb_strlen($data['login']) > 6 && mb_strlen($data['login']) < 20) && !preg_match("/[a-zA-Z0-9_]/ui", $data['login'])) {

                die(Other::generateJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]));

			} elseif (isset($data['login']) && (mb_strlen($data['login']) > 6 && mb_strlen($data['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $data['login']) && Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $data['login'])->getNumRows()) {

                die(Other::generateJson(["response" => ["error" => "the user is already registered"]]));

			}

		break;
		case 'restorePassword':
			if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s' AND email = '?s'", $data['login'], $data['email'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "login/email pair is not correct"]]));

            if (!isset($data['confirmCode'])) {

                $getCode = json_decode(Email::createCode($data['email'], Mail::getInstance()), true);

                if ($getCode['response']['status'] !== "ok") die(Other::generateJson(["response" => ["error" => $getCode['response']['error']]]));

                die(Other::generateJson(["response" => ["status" => "confirm your email", "hash" => $getCode['response']["hash"]]]));

            } else {

                $confirmCode = json_decode(Email::confirmCode($data['email'], $data['confirmCode'], $data['hash']));

                if ($confirmCode['response'] !== true) die(Other::generateJson(["response" => ["error" => $confirmCode['response']['error']]]));

                die(User::restorePassword($data['email'], $data['newPassword']));

            }

        case 'setOnline':
            die(User::setOnline($data['token']));

        case 'setOffline':
			die(User::setOffline($data['token']));

        case 'changeName':
            if (isset($data['name']) && isset($data['email']) && isset($data['code']) && isset($data['hashCode'])) die(User::changeName($data['name'], $data['email'], $data['code'], $data['hashCode']));

        break;

        default:
			die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {

    $tokenStatus = Other::checkToken($_GET['token']);

    if (!in_array($_GET['method'], ["auth"])) $tokenStatus == true or die($tokenStatus);

	switch ($_GET['method']) {

		case 'auth':
	
			if (isset($_GET['login']) && (mb_strlen($_GET['login']) > 6 && mb_strlen($_GET['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $_GET['login']) && Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $_GET['login'])->getNumRows()) {
	
				if (isset($_GET['password']) && (mb_strlen($_GET['password']) > 8 && mb_strlen($_GET['password']) < 60) && preg_match("/[a-zA-Z0-9_]/ui", $_GET['password'])) {
	
					die(User::auth($_GET['login'], $_GET['password']));
	
				} elseif (!isset($_GET['password'])) {

                    die(Other::generateJson(["response" => ["error" => "password not setted"]]));
	
				} elseif (isset($_GET['password']) && (mb_strlen($_GET['password']) <= 8 || $_GET['password'] >= 60)) {

                    die(Other::generateJson(["response" => ["error" => "the password is too big or too small"]]));
	
				} elseif (isset($_GET['password']) && (mb_strlen($_GET['password']) > 8 && $_GET['password'] < 60) && !preg_match("/[a-zA-Z0-9_]/ui", $_GET['password'])) {

                    die(Other::generateJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]));
	
				}
	
			} elseif (!isset($_GET['login'])) {

                die(Other::generateJson(["response" => ["error" => "login not setted"]]));
	
			} elseif (isset($_GET['login']) && (mb_strlen($_GET['login']) <= 6 || mb_strlen($_GET['login']) >= 20)) {

                die(Other::generateJson(["response" => ["error" => "the login is too big or too small"]]));
	
			} elseif (isset($_GET['login']) && (mb_strlen($_GET['login']) > 6 && mb_strlen($_GET['login']) < 20) && !preg_match("/[a-zA-Z0-9_]/ui", $_GET['login'])) {

                die(Other::generateJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]));
	
			} elseif (isset($_GET['login']) && (mb_strlen($_GET['login']) > 6 && mb_strlen($_GET['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $_GET['login']) && !Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $_GET['login'])->getNumRows()) {

                die(Other::generateJson(["response" => ["error" => "the user not found"]]));
	
			}

		break;
		default:
            die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]));

    }

}
