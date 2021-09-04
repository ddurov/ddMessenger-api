<?php

namespace Eviger;

use Eviger\Contracts\Singleton;
use Krugozor\Database\Mysql;
use Krugozor\Database\MySqlException;
use Dotenv\Dotenv;

class Database implements Singleton
{
    private static ?Mysql $instance = null;

    /**
     * @throws MySqlException
     */
    public static function getInstance(): Mysql
    {
        Dotenv::createImmutable("/var/www/tools")->load();
        if (self::$instance === null) {
            self::$instance = Mysql::create($_ENV['serverDatabase'], $_ENV['loginDatabase'], $_ENV['passwordDatabase'])
                ->setDatabaseName($_ENV['nameDatabase'])
                ->setCharset("utf8mb4");
        }

        return self::$instance;
    }

    //singleton
    private function __construct(){}

    private function __clone(){}
}