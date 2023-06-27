<?php

ini_set('display_errors', '1');

chdir("app"); // хуй знает почему рут директория стоит на /var/www, но это костыль для решения

require_once "../vendor/autoload.php";

use Api\Singletone\Database;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

try {
    ConsoleRunner::run(new SingleManagerProvider(Database::getInstance()));
} catch (ORMException|\Doctrine\DBAL\Exception $e) {
    echo "CLI error: {$e->getMessage()}";
}