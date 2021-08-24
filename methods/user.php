<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";
require_once "tools/api.php";
require_once "tools/checkDevice.php";
require_once "tools/functions.php";

use Krugozor\Database\Mysql;
use Eviger\EvigerAPI\API;

$detect = new Mobile_Detect;
$db = Mysql::create("localhost", "user", "password")->setDatabaseName("eviger")->setCharset("utf8mb4");
$api = API::create("");
$deviceType = ($detect->isMobile() || $detect->isTablet() ? 2 : 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$data = json_decode(file_get_contents('php://input'), true);

    (in_array($data['method'], ["registerAccount", "restorePassword"])) ? true : checkToken($data['token']);

	switch ($data['method']) {

		case 'registerAccount':

			$salt = bin2hex(random_bytes(8));

			if (isset($data['login']) && (mb_strlen($data['login']) > 6 && mb_strlen($data['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $data['login']) && !$db->query("SELECT * FROM eviger_users WHERE login = '?s'", $data['login'])->getNumRows()) {

				if (isset($data['password']) && (mb_strlen($data['password']) > 8 && mb_strlen($data['password']) < 60) && preg_match("/[a-zA-Z0-9_]/ui", $data['password'])) {

					if (isset($data['email']) && preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email']) && !$db->query("SELECT * FROM eviger_users WHERE email = '?s'", $data['email'])->getNumRows()) {

						if (isset($data['registration_email_code'])) {

							$getCodeEmailStatus = $api->requestPost("email", ["method" => "confirmCode", "email" => $data['email'], "code" => $data['registration_email_code'], "hash" => $data['hashCode']]);

							if (!isset($getCodeEmailStatus['response']['error'])) {

								$token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);

								$db->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $data['email']);
								$db->query("INSERT INTO eviger_sessions (login, date_auth, session_type_device, ip_device) VALUES ('?s', ?i, ?i, '?s')", $data['login'], time(), $deviceType, $_SERVER['REMOTE_ADDR']);

								if ($data['username'] !== null) {

									$db->query("INSERT INTO eviger_users (login, password_hash, password_salt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')", $data['login'], md5($data['password'].$salt), $salt, $data['username'], $data['email']);

								} else {

									$db->query("INSERT INTO eviger_users (login, password_hash, password_salt, username, email) VALUES ('?s', '?s', '?s', '?s', '?s')", $data['login'], md5($data['password'].$salt), $salt, NULL, $data['email']);
									$db->query("UPDATE eviger_users SET username = 'eid?i' WHERE login = '?s'", $db->query("SELECT * FROM eviger_users")->getNumRows(), $data['login']);

								}

								$db->query("INSERT INTO eviger_tokens (eid, token) VALUES (?i, '?s')", (int)$db->query("SELECT id FROM eviger_users WHERE login = '?s'", $data['login'])->fetchAssoc()['id'], $token);

								echo sendJson(["response" => ["status" => "ok", "token" => $token]]);

							} else {

								echo sendJson(["response" => ["error" => $getCodeEmailStatus['response']['error']]]);

							}

						} else {

							$request = $api->requestPost("email", ["method" => "createCode", "email" => $data['email']]);

							isset($request['response']['error']) ? die(sendJson(["response" => ["error" => $request['response']['error']]])) : die(sendJson(["response" => ["status" => "confirm your email", "hash" => $request['response']["hash"]]]));

						}

					} elseif (!isset($data['email'])) {

						echo sendJson(["response" => ["error" => "email not setted"]]);

					} elseif (isset($data['email']) && !preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email'])) {

						echo sendJson(["response" => ["error" => "email incorrect"]]);

					} elseif (isset($data['email']) && preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email']) && $db->query("SELECT * FROM eviger_users WHERE email = '?s'", $data['email'])->getNumRows()) {

						echo sendJson(["response" => ["error" => "email is busy"]]);

					}

				} elseif (!isset($data['password'])) {

					echo sendJson(["response" => ["error" => "password not setted"]]);

				} elseif (isset($data['password']) && (mb_strlen($data['password']) < 8 || mb_strlen($data['password']) > 60)) {

					echo sendJson(["response" => ["error" => "the password is too big or too small"]]);

				} elseif (isset($data['password']) && (mb_strlen($data['password']) > 8 && mb_strlen($data['password']) < 60) && !preg_match("/[a-zA-Z0-9_]/ui", $data['password'])) {

					echo sendJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]);

				}

			} elseif (!isset($data['login'])) {

				echo sendJson(["response" => ["error" => "login not setted"]]);

			} elseif (isset($data['login']) && (mb_strlen($data['login']) <= 6 || mb_strlen($data['login']) >= 20)) {

				echo sendJson(["response" => ["error" => "the login is too big or too small"]]);

			} elseif (isset($data['login']) && (mb_strlen($data['login']) > 6 && mb_strlen($data['login']) < 20) && !preg_match("/[a-zA-Z0-9_]/ui", $data['login'])) {

				echo sendJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]);

			} elseif (isset($data['login']) && (mb_strlen($data['login']) > 6 && mb_strlen($data['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $data['login']) && $db->query("SELECT * FROM eviger_users WHERE login = '?s'", $data['login'])->getNumRows()) {

				echo sendJson(["response" => ["error" => "the user is already registered"]]);

			}

		break;
		case 'restorePassword':
			
			if ($db->query("SELECT * FROM eviger_users WHERE login = '?s' AND email = '?s'", $data['login'], $data['email'])->getNumRows()) {

				if (!isset($data['confirmCode'])) {

					$request = $api->requestPost("email", ["method" => "createCode", "email" => $data['email']]);

					isset($request['response']['error']) ? die(sendJson(["response" => ["error" => $request['response']['error']]])) : die(sendJson(["response" => ["status" => "confirm your email", "hash" => $request['response']["hash"]]]));

				} else {

					$request = $api->request("email", ["method" => "confirmCode", "email" => $data['email'], "code" => $data['code'], "hash" => $data['hash']]);

					if (!isset($request['response']['error'])) {

						$salt = bin2hex(random_bytes(8));
						$token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 77);
						$idAccount = $db->query("SELECT id FROM eviger_users WHERE email = '?s'", $data['email'])->fetchAssoc()['id'];
						$db->query("UPDATE eviger_users SET password_hash = '?s', password_salt = '?s' WHERE id = ?i", md5($data['password'].$salt), $salt, $idAccount);
						$db->query("UPDATE eviger_users SET token = '?s' WHERE eid = ?i", $token, $idAccount);

					} else {

						echo sendJson(["response" => ["error" => $request['response']['error']]]);

					}

				}

			} else {

				echo sendJson(["response" => ["error" => "login/email pair is not correct"]]);

			}

		break;
		case 'setOnline':

			$db->query("UPDATE eviger_users SET online = 1, lastSeen = 0 WHERE id = ?i", $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $data['token'])->fetchAssoc()['eid']);
			echo sendJson(["response" => ["status" => "ok"]]);

		break;
		case 'setOffline':

			$db->query("UPDATE eviger_users SET online = 0, lastSeen = ?i WHERE id = ?i", time(), $db->query("SELECT eid FROM eviger_tokens WHERE token = '?s'", $data['token'])->fetchAssoc()['eid']);
			echo sendJson(["response" => ["status" => "ok"]]);

		break;
		default:

			echo sendJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]);

		break;

	}

} else {

	(in_array($_GET['method'], ["auth"])) ? true : checkToken($_GET['token']);

	switch ($_GET['method']) {

		case 'auth':

			$salt = $db->query("SELECT password_salt FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['password_salt'];
	
			if (isset($_GET['login']) && (mb_strlen($_GET['login']) > 6 && mb_strlen($_GET['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $_GET['login']) && $db->query("SELECT * FROM eviger_users WHERE login = '?s'", $_GET['login'])->getNumRows()) {
	
				if (isset($_GET['password']) && (mb_strlen($_GET['password']) > 8 && mb_strlen($_GET['password']) < 60) && preg_match("/[a-zA-Z0-9_]/ui", $_GET['password'])) {
	
					if (md5($_GET['password'].$salt) == $db->query("SELECT password_hash FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['password_hash']) {
	
						if ($db->query("SELECT * FROM eviger_attempts_auth WHERE login = '?s'", $_GET['login'])->getNumRows() >= 5) {
	
							echo sendJson(["response" => ["error" => "too many authorizations, account has been frozen"]]);
	
						} else {
	
							$token = $db->query("SELECT * FROM eviger_tokens WHERE eid = ?i", $db->query("SELECT id FROM eviger_users WHERE login = '?s'", $_GET['login'])->fetchAssoc()['id'])->fetchAssoc()['token'];
	
							$db->query("INSERT INTO eviger_sessions (login, date_auth, session_type_device, ip_device) VALUES ('?s', ?i, ?i, '?s')", $_GET['login'], time(), $deviceType, $_SERVER['REMOTE_ADDR']);
							$db->query("INSERT INTO eviger_attempts_auth (login, time, auth_ip) VALUES ('?s', ?i, '?s')", $_GET['login'], time(), $_SERVER['REMOTE_ADDR']);
	
							echo sendJson(["response" => ["status" => "ok", "token" => $token]]);
	
						}
	
					} else {
	
						echo sendJson(["response" => ["error" => "invalid login or password"]]);
	
					}
	
				} elseif (!isset($_GET['password'])) {
	
					echo sendJson(["response" => ["error" => "password not setted"]]);
	
				} elseif (isset($_GET['password']) && (mb_strlen($_GET['password']) <= 8 || $_GET['password'] >= 60)) {
	
					echo sendJson(["response" => ["error" => "the password is too big or too small"]]);
	
				} elseif (isset($_GET['password']) && (mb_strlen($_GET['password']) > 8 && $_GET['password'] < 60) && !preg_match("/[a-zA-Z0-9_]/ui", $_GET['password'])) {
	
					echo sendJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]);
	
				}
	
			} elseif (!isset($_GET['login'])) {
	
				echo sendJson(["response" => ["error" => "login not setted"]]);
	
			} elseif (isset($_GET['login']) && (mb_strlen($_GET['login']) <= 6 || mb_strlen($_GET['login']) >= 20)) {
	
				echo sendJson(["response" => ["error" => "the login is too big or too small"]]);
	
			} elseif (isset($_GET['login']) && (mb_strlen($_GET['login']) > 6 && mb_strlen($_GET['login']) < 20) && !preg_match("/[a-zA-Z0-9_]/ui", $_GET['login'])) {
	
				echo sendJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]);
	
			} elseif (isset($_GET['login']) && (mb_strlen($_GET['login']) > 6 && mb_strlen($_GET['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $_GET['login']) && !$db->query("SELECT * FROM eviger_users WHERE login = '?s'", $_GET['login'])->getNumRows()) {
	
				echo sendJson(["response" => ["error" => "the user not found"]]);
	
			}

		break;
		default:

			echo sendJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]);

		break;

	}

}