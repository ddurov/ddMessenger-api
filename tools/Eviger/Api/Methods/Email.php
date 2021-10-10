<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{

    /**
     * @param string $email
     * @param PHPMailer $mail
     * @return string
     */
    public static function createCode(string $email, PHPMailer $mail): string
    {

        try {

            if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $email)) return Other::generateJson(["response" => ["error" => "email incorrect"]]);

            if (preg_match("/(.*)?eviger\.ru/isu", $email)) return Other::generateJson(["response" => ["error" => "email must not contain domains of any level eviger.ru"]]);

            $code = mb_substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16);
            $hash = md5($code . "|" . bin2hex(random_bytes(8)));

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->getNumRows()) {
                if ((time() - Database::getInstance()->query("SELECT date_request FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['date_request']) < 300 || Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE ip_request = '?s'", $_SERVER['REMOTE_ADDR'])->getNumRows()) return Other::generateJson(["response" => ["error" => "cooldown"]]);

                Database::getInstance()->query("UPDATE eviger.eviger_codes_email SET code = '?s', date_request = ?i, hash = '?s' WHERE email = '?s'", $code, time(), $hash, $email);
            } else {
                Database::getInstance()->query("INSERT INTO eviger.eviger_codes_email (code, email, date_request, ip_request, hash) VALUES ('?s', '?s', ?i, '?s')", $code, $email, time(), $_SERVER['REMOTE_ADDR'], $hash);
            }

            $mail->setFrom(getenv("USER_MAIL_DOMAIN"));
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Подтверждение почты";
            $mail->Body = "Ваш код подтверждения: <b>$code</b>\n\nДанный код будет активен в течении часа с момента получения письма\nЕсли вы не запрашивали данное письмо <b>немендленно смените пароль</b>";
            $mail->CharSet = "UTF-8";
            $mail->Encoding = 'base64';

            $mail->send();

            return Other::generateJson(["response" => ["status" => "ok", "hash" => $hash]]);

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

    /**
     * @param string $email
     * @param string $code
     * @param string $hash
     * @return string
     */
    public static function confirmCode(string $email, string $code, string $hash): string
    {

        try {

            if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s' AND code = '?s'", $email, $code)->getNumRows()) {

                if ($hash == Database::getInstance()->query("SELECT hash FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['hash']) {

                    return Other::generateJson(["response" => true]);

                } else {

                    return Other::generateJson(["response" => ["error" => "incorrect hash"]]);

                }

            } else {

                return Other::generateJson(["response" => ["error" => "incorrect code"]]);

            }

        } catch (Exception $e) {
            Other::log($e->getMessage());
            return "Internal error";
        }

    }

}