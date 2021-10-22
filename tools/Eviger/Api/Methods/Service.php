<?php

declare(strict_types=1);

namespace Eviger\Api\Methods;

use Eviger\Api\DTO\Response;
use Eviger\Database;

class Service
{

    /**
     * @return string
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
}