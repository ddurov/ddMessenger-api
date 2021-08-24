<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";
require_once "tools/functions.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use Krugozor\Database\Mysql;

$db = Mysql::create("localhost", "user", "password")->setDatabaseName("eviger")->setCharset("utf8mb4");
$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = 'host';
$mail->SMTPAuth = true;
$mail->Username = 'user';
$mail->Password = 'password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($data['method']) {
        
        case 'createCode':
            
            if (isset($data['email'])) {
                
                if (preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $data['email'])) {
                    
                    
                    
                    $code = mb_substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16);
                    $hash = md5($code."|".bin2hex(random_bytes(8)));
                    
                    $db->query("SELECT * FROM eviger_codes_email WHERE email = '?s'", $data['email'])->getNumRows() ?
                        (time() - $db->query("SELECT date_request FROM eviger_codes_email WHERE email = '?s'", $data['email'])->fetchAssoc()['date_request']) > 300 ? 
                            $db->query("UPDATE eviger_codes_email SET code = '?s', date_request = ?i, hash = '?s' WHERE email = '?s'", $code, time(), $hash, $data['email'])
                            :
                            die(sendJson(["response" => ["error" => "cooldown"]]))
                        :
                        $db->query("INSERT INTO eviger_codes_email (code, email, date_request, hash) VALUES ('?s', '?s', ?i, '?s')", $code, $data['email'], time(), $hash);
                    
                    $mail->setFrom('user@host');
                    $mail->addAddress($data['email']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = "Подтверждение почты";
                    $mail->Body = "Ваш код подтверждения: <b>$code</b>";
                    $mail->CharSet = "UTF-8";
                    $mail->Encoding = 'base64';
                    
                    $mail->send();
                    
                    die(sendJson(["response" => ["status" => "ok", "hash" => $hash]]));
                    
                } else {
                    
                    echo sendJson(["response" => ["error" => "email incorrect"]]);
                    
                }
                
            } else {
                
                echo sendJson(["response" => ["error" => "email not setted"]]);
                
            }

        break;
        case 'confirmCode':

            if (isset($data['email'])) {
    
                if (isset($data['code'])) {
                    
                    if ($db->query("SELECT * FROM eviger_codes_email WHERE email = '?s' AND code = '?s'", $data['email'], $data['code'])->getNumRows()) {
                        
                        if ($data['hash'] == $db->query("SELECT hash FROM eviger_codes_email WHERE email = '?s'", $data['email'])->fetchAssoc()['hash']) {
                        
                            $db->query("DELETE FROM eviger_codes_email WHERE email = '?s'", $data['email']);
                            echo sendJson(["response" => true]);
                            
                        } else {
                            
                            echo sendJson(["response" => ["error" => "incorrect hash"]]);
                            
                        }
                        
                    } else {
                        
                        echo sendJson(["response" => ["error" => "incorrect code"]]);
                        
                    }
                    
                } else {
                    
                    echo sendJson(["response" => ["error" => "code not setted"]]);
                    
                }
                
            } else {
                
                echo sendJson(["response" => ["error" => "email not setted"]]);
                
            }

        break;
    	default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]);
            
        break;

    }

} else {
    
    switch ($_GET['method']) {

    	default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]);
            
        break;

    }

}
