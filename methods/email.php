<?php

header('Access-Control-Allow-Origin: *');

require_once "tools/vendor/autoload.php";

use Eviger\Api\Methods\Email;
use Eviger\Api\Tools\Other;
use PHPMailer\PHPMailer\PHPMailer;

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

                (new Email)->createCode($data['email'], $mail);
                
            } else {

                die((new Other)->generateJson(["response" => ["error" => "email not setted"]]));
                
            }

        break;
        case 'confirmCode':

            if (isset($data['email'])) {
    
                if (isset($data['code'])) {

                    (new Email)->confirmCode($data['email'], $data['code'], $data['hash']);
                    
                } else {

                    die((new Other)->generateJson(["response" => ["error" => "code not setted"]]));
                    
                }
                
            } else {

                die((new Other)->generateJson(["response" => ["error" => "email not setted"]]));
                
            }

        break;
    	default:
            die((new Other)->generateJson(["response" => ["error" => "unknown method", "parameters" => $data === null ? [] : $data]]));

    }

} else {
    
    switch ($_GET['method']) {

    	default:

            echo sendJson(["response" => ["error" => "unknown method", "parameters" => $_GET]]);
            
        break;

    }

}
