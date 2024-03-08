<?php declare(strict_types=1);

namespace Api\Singleton;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer implements Singleton
{
    private static ?PHPMailer $mailer = null;

    /**
     * @throws Exception
     */
    public static function getInstance(): PHPMailer
    {
        if (self::$mailer === null) {
            self::$mailer = new PHPMailer(true);
            self::$mailer->isSMTP();
            self::$mailer->Host = getenv("MAIL_SERVER");
            self::$mailer->Helo = getenv("MAIL_SERVER");
            self::$mailer->SMTPAuth = true;
            self::$mailer->Username = getenv("MAIL_USER");
            self::$mailer->Password = getenv("MAIL_PASSWORD");
            self::$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            self::$mailer->Port = 587;
            self::$mailer->SMTPOptions = [
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            ];
            self::$mailer->XMailer = "notificator";
            self::$mailer->CharSet = "utf-8";
            self::$mailer->setFrom(getenv("MAIL_USER"), explode("@", getenv("MAIL_USER"))[0]);
        }
        return self::$mailer;
    }
}