<?php declare(strict_types=1);

namespace Api\Singleton;

use Core\Exceptions\InternalError;
use Core\Singleton;
use Doctrine\ORM\EntityManager;

class Database implements Singleton
{
	private static ?\Core\Database $database = null;

	/**
	 * @throws InternalError
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
	 * @throws InternalError
	 */
	public static function getEntityManager(): EntityManager
	{
	    return self::getInstance()->getEntityManager();
	}
}