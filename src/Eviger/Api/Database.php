<?php

namespace Eviger;

use Eviger\Contracts\Singleton;
use Krugozor\Database\Mysql;
use Krugozor\Database\MySqlException;


class Database implements Singleton
{
    private static ?Mysql $instance = null;

    private const SERVER = "localhost";
    private const USERNAME = "user";
    private const PASSWORD = "password";
    private const DATABASE_NAME = "eviger";
    private const CHARSET = "utf8mb4";

    /**
     * @throws MySqlException
     */
    public static function getInstance(): Mysql
    {
        if (self::$instance === null) {
            self::$instance = Mysql::create(self::SERVER, self::USERNAME, self::PASSWORD)
                ->setDatabaseName(self::DATABASE_NAME)
                ->setCharset(self::CHARSET);
        }

        return self::$instance;
    }

    //singleton
    private function __construct(){}

    private function __clone(){}
}