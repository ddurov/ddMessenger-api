<?php
declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use Exception;
use Krugozor\Database\MySqlException;

class Email
{

    /**
     * @param string $email
     * @return string
     * @throws selfThrows
     * @throws Exception
     */
    public static function createCode(string $email): string
    {

        if (!preg_match("/^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$/", $email)) throw new selfThrows(["message" => "email incorrect"]);

        if (preg_match("/(.*)?eviger\.ru/isu", $email)) throw new selfThrows(["message" => "email must not contain domains of any level eviger.ru"]);

        $code = mb_substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16);
        $hash = md5($code . "|" . bin2hex(random_bytes(16)));

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE requestIp = '?s'", $_SERVER['REMOTE_ADDR'])->getNumRows()) return (new Response)
            ->setCode(100)
            ->setStatus("ok")
            ->setResponse(["message" => "code has already been requested", "hash" => Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE requestIp = '?s'", $_SERVER['REMOTE_ADDR'])->fetchAssoc()["hash"]])
            ->toJson();

        if (Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->getNumRows()) {
            if (time() - Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['requestTime'] > 300) throw new selfThrows(["message" => "code has already been requested", "hash" => Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE requestIp = '?s'", $_SERVER['REMOTE_ADDR'])->fetchAssoc()["hash"]]);

            Database::getInstance()->query("UPDATE eviger.eviger_codes_email SET code = '?s', requestTime = ?i, hash = '?s' WHERE email = '?s'", $code, time(), $hash, $email);
        } else {
            Database::getInstance()->query("INSERT INTO eviger.eviger_codes_email (code, email, requestTime, requestIp, hash) VALUES ('?s', '?s', ?i, '?s', '?s')", $code, $email, time(), $_SERVER['REMOTE_ADDR'], $hash);
        }

        mail($email, "Подтверждение почты", "Ваш код подтверждения: <b>$code</b><br>Данный код будет активен в течении часа с момента получения письма<br>Если вы не запрашивали данное письмо <b>немедленно смените пароль</b>", ["From" => getenv("USER_MAIL_DOMAIN"), "MIME-Version" => 1.0, "Content-type" => "text/html; charset=utf-8"]);

        return (new Response)
            ->setCode(201)
            ->setStatus("ok")
            ->setResponse(["hash" => $hash])
            ->toJson();

    }

    /**
     * @param string $email
     * @param string $code
     * @param string $hash
     * @return string
     * @throws selfThrows|MySqlException
     */
    public static function confirmCode(string $email, string $code, string $hash): string
    {

        if (!Database::getInstance()->query("SELECT * FROM eviger.eviger_codes_email WHERE email = '?s' AND code = '?s'", $email, $code)->getNumRows()) throw new selfThrows(["message" => "invalid code"], 400);

        if ($hash !== Database::getInstance()->query("SELECT hash FROM eviger.eviger_codes_email WHERE email = '?s'", $email)->fetchAssoc()['hash']) throw new selfThrows(["message" => "invalid hash"], 400);

        return (new Response)
            ->setStatus("ok")
            ->setResponse(true)
            ->toJson();

    }

}