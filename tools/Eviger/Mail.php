<?php

declare(strict_types=1);

namespace Eviger;

use Eviger\Contracts\Singleton;
use PHPMailer\PHPMailer\PHPMailer;

class Mail implements Singleton
{
    private static ?PHPMailer $instance = null;

    public static function getInstance(): PHPMailer
    {
        if (self::$instance === null) {
            self::$instance = new PHPMailer();
            self::$instance->isSMTP();
            self::$instance->Host = getenv("MAIL_SERVER");
            self::$instance->SMTPAuth = true;
            self::$instance->Username = getenv("MAIL_LOGIN");
            self::$instance->Password = getenv("MAIL_PASSWORD");
            self::$instance->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            self::$instance->Port = 587;
        }

        return self::$instance;
    }

    //singleton
    private function __construct(){}

    private function __clone(){}
}