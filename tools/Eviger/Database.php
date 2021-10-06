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
            self::$instance = Mysql::create(getenv("DATABASE_SERVER"), getenv("DATABASE_LOGIN"), getenv("DATABASE_PASSWORD"))
                ->setDatabaseName(getenv("DATABASE_NAME"))
                ->setCharset("utf8mb4");
        }

        return self::$instance;
    }

    //singleton
    private function __construct(){}

    private function __clone(){}
}