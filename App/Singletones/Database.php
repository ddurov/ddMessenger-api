<?php declare(strict_types=1);

namespace Api\Singletones;

use Api\Contracts\Singleton;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;

class Database implements Singleton
{
    private static ?EntityManager $database = null;

    /**
     * @throws ORMException
     * @throws Exception
     */
    public static function getInstance(): EntityManager
    {
        if (self::$database === null) {
            self::$database = (new \Core\Database())->create(
                "ddmessager",
                getenv("DATABASE_LOGIN"),
                getenv("DATABASE_PASSWORD"),
                getenv("DATABASE_SERVER"),
                __DIR__."/../"
            );
        }
        return self::$database;
    }
}