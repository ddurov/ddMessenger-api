<?php declare(strict_types=1);

namespace Api\Singleton;

use Core\Singleton;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;

class Database implements Singleton
{
	private static ?\Core\Database $database = null;

	/**
	 * @return \Core\Database
	 * @throws Exception
	 * @throws MissingMappingDriverImplementation
	 */
	public static function getInstance(): \Core\Database
	{
		if (self::$database === null) {
			self::$database = new \Core\Database(
				__DIR__ . "/../",
				"ddProjects-messenger",
				"user",
				getenv("DATABASE_PASSWORD"),
				getenv("DATABASE_SERVER"),
				(int)getenv("DATABASE_PORT")
			);
		}
		return self::$database;
	}

	/**
	 * @return EntityManager
	 */
	public static function getEntityManager(): EntityManager
	{
	    return self::$database->getEntityManager();
	}
}