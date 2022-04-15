<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Database;
use Krugozor\Database\MySqlException;

class Service
{

    /**
     * @return string
     * @throws MySqlException
     */
    public static function getUpdates(): string
    {

        return (new Response)
            ->setStatus("ok")
            ->setResponse([
                "version" => Database::getInstance()->query("SELECT version FROM eviger.eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['version'],
                "download_link" => "https://" . explode("/var/www/", Database::getInstance()->query("SELECT dl FROM eviger.eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['dl'])[1],
                "changelog" => Database::getInstance()->query("SELECT changelog FROM eviger.eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['changelog']
            ])
            ->toJson();

    }

    public static function getPinningHashByDomain(string $domain): string
    {
        if (gethostbyname($domain) === $domain) throw new selfThrows(["message" => "domain parameter invalid or not created"], 400);

        return (new Response)
            ->setStatus("ok")
            ->setResponse([
                "key" => shell_exec("sh ../filesEviger/Eviger/Api/Tools/getKeyPinning.sh " . $domain)
            ])
            ->toJson();
    }
}