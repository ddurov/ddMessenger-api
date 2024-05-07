<?php declare(strict_types=1);

namespace Api\Singleton;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;

class Database implements Singleton
{
    private static ?EntityManager $database = null;

    /**
     * @throws Exception|MissingMappingDriverImplementation
     */
    public static function getInstance(): EntityManager
    {
        if (self::$database === null) {
            self::$database = (new \Core\Database())->create(
                getenv("DATABASE_NAME"),
                "user",
                getenv("DATABASE_PASSWORD"),
                getenv("DATABASE_SERVER"),
                (int) getenv("DATABASE_PORT"),
                __DIR__."/../"
            );
        }
        return self::$database;
    }
}