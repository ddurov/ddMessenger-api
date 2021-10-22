<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{

    /**
     * @param string $email
     * @param PHPMailer $mail
     * @return string
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws selfThrows
     * @throws Exception
     */
    public static function createCode(string $email, PHPMailer $mail): string
    {

        if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $email)) throw new selfThrows(["message" => "email incorrect"]);

        if (preg_match("/(.*)?eviger\.ru/isu", $email)) throw new selfThrows(["message" => "email must not contain domains of any level eviger.ru"]);

        $code = mb_substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16);
        $hash = md5($code . "|" . bin2hex(random_bytes(8)));

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->getNumRows()) {
            if ((time() - Database::getInstance()->query("SELECT date_request FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['date_request']) < 300 || Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE ip_request = '?s'", $_SERVER['REMOTE_ADDR'])->getNumRows()) throw new selfThrows(["message" => "cooldown"]);

            Database::getInstance()->query("UPDATE eviger.eviger_codes_email SET code = '?s', date_request = ?i, hash = '?s' WHERE email = '?s'", $code, time(), $hash, $email);
        } else {
            Database::getInstance()->query("INSERT INTO eviger.eviger_codes_email (code, email, date_request, ip_request, hash) VALUES ('?s', '?s', ?i, '?s', '?s')", $code, $email, time(), $_SERVER['REMOTE_ADDR'], $hash);
        }

        $mail->setFrom(getenv("USER_MAIL_DOMAIN"));
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Подтверждение почты";
        $mail->Body = 'Ваш код подтверждения: <b>'.$code.'</b><br>Данный код будет активен в течении часа с момента получения письма<br>Если вы не запрашивали данное письмо <b>немендленно смените пароль</b>';
        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";

        $mail->send();

        return (new Response)
            ->setStatus("ok")
            ->setResponse(["hash" => $hash])
            ->toJson();

    }

    /**
     * @param string $email
     * @param string $code
     * @param string $hash
     * @return string
     * @throws selfThrows
     */
    public static function confirmCode(string $email, string $code, string $hash): string
    {

        if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s' AND code = '?s'", $email, $code)->getNumRows()) throw new selfThrows(["message" => "invalid hash"]);

        if ($hash !== Database::getInstance()->query("SELECT hash FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['hash']) throw new selfThrows(["message" => "invalid hash"]);

        return (new Response)
            ->setStatus("ok")
            ->setResponse([true])
            ->toJson();

    }

}