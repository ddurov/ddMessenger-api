<?php

namespace Eviger;

use Eviger\Contracts\Singleton;
use Krugozor\Database\Mysql;
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;

class Mail implements Singleton
{
    private static ?PHPMailer $instance = null;

    public static function getInstance(): PHPMailer
    {
        Dotenv::createImmutable("/var/www/tools")->load();
        if (self::$instance === null) {
            self::$instance = new PHPMailer();
            self::$instance->isSMTP();
            self::$instance->Host = 'host';
            self::$instance->SMTPAuth = true;
            self::$instance->Username = 'user';
            self::$instance->Password = 'password';
            self::$instance->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            self::$instance->Port = 587;
        }

        return self::$instance;
    }

    //singleton
    private function __construct(){}

    private function __clone(){}
}