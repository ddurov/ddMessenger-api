<?php

require_once "vendor/autoload.php";

use Api\Singletones\Database;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

try {
    ConsoleRunner::run(new SingleManagerProvider(Database::getInstance()));
} catch (\Doctrine\DBAL\Exception|ORMException $e) {
    echo "CLI error: {$e->getMessage()}";
}