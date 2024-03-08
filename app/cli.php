<?php

ini_set('display_errors', 1);

require_once "vendor/autoload.php";

use Api\Singleton\Database;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

try {
    ConsoleRunner::run(new SingleManagerProvider(Database::getInstance()));
} catch (\Doctrine\DBAL\Exception|MissingMappingDriverImplementation $e) {
    echo "CLI error: {$e->getMessage()}";
}