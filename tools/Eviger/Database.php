<?php

namespace Eviger;

use Eviger\Contracts\Singleton;
use Krugozor\Database\Mysql;

class Database implements Singleton
{
    private static ?Mysql $instance = null;

    public static function getInstance(): Mysql
    {
        if (self::$instance === null) {
            self::$instance = Mysql::create("localhost", "login", "password")
                ->setDatabaseName("name")
                ->setCharset("utf8mb4");
        }

        return self::$instance;
    }

    //singleton
    private function __construct(){}

    private function __clone(){}
}