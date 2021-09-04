<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;
use Krugozor\Database\MySqlException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    /**
     * @throws MySqlException|Exception
     * @throws \Exception
     */
    public function createCode(string $email, PHPMailer $mail) {

        if (preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $email)) {

            $code = mb_substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16);
            $hash = md5($code."|".bin2hex(random_bytes(8)));

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->getNumRows()) {
                if ((time() - Database::getInstance()->query("SELECT date_request FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['date_request']) > 300) {
                    Database::getInstance()->query("UPDATE eviger.eviger_codes_email SET code = '?s', date_request = ?i, hash = '?s' WHERE email = '?s'", $code, time(), $hash, $email);
                } else {
                    die((new Other)->generateJson(["response" => ["error" => "cooldown"]]));
                }
            } else {
                Database::getInstance()->query("INSERT INTO eviger.eviger_codes_email (code, email, date_request, hash) VALUES ('?s', '?s', ?i, '?s')", $code, $email, time(), $hash);
            }

            $mail->setFrom('user@host');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Подтверждение почты";
            $mail->Body = "Ваш код подтверждения: <b>$code</b>";
            $mail->CharSet = "UTF-8";
            $mail->Encoding = 'base64';

            $mail->send();

            die((new Other)->generateJson(["response" => ["status" => "ok", "hash" => $hash]]));

        } else {

            die((new Other)->generateJson(["response" => ["error" => "email incorrect"]]));

        }

    }

    /**
     * @throws MySqlException
     */
    public function confirmCode(string $email, string $code, string $hash) {

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s' AND code = '?s'", $email, $code)->getNumRows()) {

            if ($hash == Database::getInstance()->query("SELECT hash FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['hash']) {

                Database::getInstance()->query("DELETE FROM eviger.eviger_codes_email WHERE email = '?s'", $email);
                die((new Other)->generateJson(["response" => true]));

            } else {

                die((new Other)->generateJson(["response" => ["error" => "incorrect hash"]]));

            }

        } else {

            die((new Other)->generateJson(["response" => ["error" => "incorrect code"]]));

        }

    }
}