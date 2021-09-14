<?php

header('Access-Control-Allow-Origin: *');

require_once "../tools/vendor/autoload.php";

use Eviger\Api\Methods\Service;
use Eviger\Api\Methods\Messages;
use Eviger\Api\Methods\Email;
use Eviger\Api\Methods\Users;
use Eviger\Api\Methods\User;
use Eviger\Api\Tools\Other;
use Eviger\Database;
use Eviger\Mail;

preg_match("~^/methods/(.*)$~", $_SERVER['REQUEST_URI'], $matches);

if (count($matches) === 0) {
    echo time();
    return;
}

if (!isset($matches[1])) die(Other::generateJson(["response" => ["error" => "method not setted"]]));

$mixedData = $_SERVER['REQUEST_METHOD'] === "GET" ? $_GET : json_decode(file_get_contents('php://input'), true);

if (!isset($mixedData['method'])) die(Other::generateJson(["response" => ["error" => "sub-method not setted"]]));

if (!in_array($mixedData['method'], ["getUpdates", "auth", "registerAccount", "restorePassword"])) {
    if (!isset($mixedData['token'])) die(Other::generateJson(["response" => ["error" => "token not setted"]]));
    $checkToken = Other::checkToken($mixedData['token']);
    if (!$checkToken) die($checkToken);
}

switch ($matches[1]) {
    
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
                    die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $mixedData === null ? [] : $mixedData]]));
                
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
                    die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $mixedData === null ? [] : $mixedData]]));

            }
        }
    case "user":
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            switch ($mixedData['method']) {
                
                case 'auth':
                    if (isset($mixedData['login']) && (mb_strlen($mixedData['login']) > 6 && mb_strlen($mixedData['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login']) && Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) {
                        
                        if (isset($mixedData['password']) && (mb_strlen($mixedData['password']) > 8 && mb_strlen($mixedData['password']) < 60) && preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) {
                            
                            User::auth($mixedData['login'], $mixedData['password']);
                            
                        } elseif (!isset($mixedData['password'])) {
                            
                            die(Other::generateJson(["response" => ["error" => "password not setted"]]));
                            
                        } elseif (isset($mixedData['password']) && (mb_strlen($mixedData['password']) <= 8 || $mixedData['password'] >= 60)) {
                            
                            die(Other::generateJson(["response" => ["error" => "the password is too big or too small"]]));
                            
                        } elseif (isset($mixedData['password']) && (mb_strlen($mixedData['password']) > 8 && $mixedData['password'] < 60) && !preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) {
                            
                            die(Other::generateJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]));
                            
                        }
                        
                    } elseif (!isset($mixedData['login'])) {
                        
                        die(Other::generateJson(["response" => ["error" => "login not setted"]]));
                        
                    } elseif (isset($mixedData['login']) && (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20)) {
                        
                        die(Other::generateJson(["response" => ["error" => "the login is too big or too small"]]));
                        
                    } elseif (isset($mixedData['login']) && (mb_strlen($mixedData['login']) > 6 && mb_strlen($mixedData['login']) < 20) && !preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) {
                        
                        die(Other::generateJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]));
                        
                    } elseif (isset($mixedData['login']) && (mb_strlen($mixedData['login']) > 6 && mb_strlen($mixedData['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login']) && !Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) {
                        
                        die(Other::generateJson(["response" => ["error" => "the user not found"]]));
                        
                    }
                    break;

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown sub-method", "parameters" => $mixedData]]));
                
            }
        } else {
            switch ($mixedData['method']) {
                case 'registerAccount':
        
		        	if (isset($mixedData['login']) && (mb_strlen($mixedData['login']) > 6 && mb_strlen($mixedData['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login']) && !Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) {
        
		        		if (isset($mixedData['password']) && (mb_strlen($mixedData['password']) > 8 && mb_strlen($mixedData['password']) < 60) && preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) {
        
		        			if (isset($mixedData['email']) && preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email']) && !Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) {
        
                                if ($mixedData['userName'] !== null && isset($mixedData['userName'])) {
        
                                    if (preg_match("/^(e)?id.*/gu", $mixedData['userName'])) die(Other::generateJson(["response" => ["error" => "username cannot contain the prefix eid or id"]]));
        
                                    if (Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE username = '?s'", $mixedData['userName'])->getNumRows()) die(Other::generateJson(["response" => ["error" => "username is busy"]]));
        
                                }
        
		        				if (isset($mixedData['registrationEmailCode'])) {
        
                                    die(User::registerAccount($mixedData['login'], $mixedData['password'], $mixedData['email'], ($mixedData['userName'] === null) ? null : $mixedData['userName'], $mixedData['registrationEmailCode'], $mixedData['hashCode']));
        
		        				} else {
        
                                    $getCode = json_decode(Email::createCode($mixedData['email'], Mail::getInstance()), true);
        
                                    if (isset($getCode['response']['error'])) {
                                        die(Other::generateJson(["response" => ["error" => $getCode['response']['error']]]));
                                    } else {
                                        die(Other::generateJson(["response" => ["status" => "confirm your email", "hash" => $getCode['response']["hash"]]]));
                                    }
        
		        				}
        
		        			} elseif (!isset($mixedData['email'])) {
        
                                die(Other::generateJson(["response" => ["error" => "email not setted"]]));
        
		        			} elseif (isset($mixedData['email']) && !preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email'])) {
        
                                die(Other::generateJson(["response" => ["error" => "email incorrect"]]));
        
		        			} elseif (isset($mixedData['email']) && preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $mixedData['email']) && Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE email = '?s'", $mixedData['email'])->getNumRows()) {
        
                                die(Other::generateJson(["response" => ["error" => "email is busy"]]));
        
		        			}
        
		        		} elseif (!isset($mixedData['password'])) {
        
                            die(Other::generateJson(["response" => ["error" => "password not setted"]]));
        
		        		} elseif (isset($mixedData['password']) && (mb_strlen($mixedData['password']) < 8 || mb_strlen($mixedData['password']) > 60)) {
        
                            die(Other::generateJson(["response" => ["error" => "the password is too big or too small"]]));
        
		        		} elseif (isset($mixedData['password']) && (mb_strlen($mixedData['password']) > 8 && mb_strlen($mixedData['password']) < 60) && !preg_match("/[a-zA-Z0-9_]/ui", $mixedData['password'])) {
        
                            die(Other::generateJson(["response" => ["error" => "the password must contain a-z, A-Z, 0-9 and _"]]));
        
		        		}
        
		        	} elseif (!isset($mixedData['login'])) {
        
                        die(Other::generateJson(["response" => ["error" => "login not setted"]]));
        
		        	} elseif (isset($mixedData['login']) && (mb_strlen($mixedData['login']) <= 6 || mb_strlen($mixedData['login']) >= 20)) {
        
                        die(Other::generateJson(["response" => ["error" => "the login is too big or too small"]]));
        
		        	} elseif (isset($mixedData['login']) && (mb_strlen($mixedData['login']) > 6 && mb_strlen($mixedData['login']) < 20) && !preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login'])) {
        
                        die(Other::generateJson(["response" => ["error" => "the login must contain a-z, A-Z, 0-9 and _"]]));
        
		        	} elseif (isset($mixedData['login']) && (mb_strlen($mixedData['login']) > 6 && mb_strlen($mixedData['login']) < 20) && preg_match("/[a-zA-Z0-9_]/ui", $mixedData['login']) && Database::getInstance()->query("SELECT * FROM eviger.eviger_users WHERE login = '?s'", $mixedData['login'])->getNumRows()) {
        
                        die(Other::generateJson(["response" => ["error" => "the user is already registered"]]));
        
		        	}
        
		        break;
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
                    if (isset($mixedData['name']) && isset($mixedData['email']) && isset($mixedData['code']) && isset($mixedData['hashCode'])) {
                        die(User::changeName($mixedData['name'], $mixedData['email'], $mixedData['code'], $mixedData['hashCode']));
                    }
                    break;

                default:
                    die(Other::generateJson(["response" => ["error" => "unknown method", "parameters" => $mixedData === null ? [] : $mixedData]]));
            }
        }
    break;
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