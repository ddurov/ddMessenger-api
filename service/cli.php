<?php

require_once "vendor/autoload.php";

use Api\Singleton\Database;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;

try {
	Database::getInstance()->executeCLI();
} catch (\Doctrine\DBAL\Exception|MissingMappingDriverImplementation $e) {
	echo "CLI error: " . $e->getMessage();
}