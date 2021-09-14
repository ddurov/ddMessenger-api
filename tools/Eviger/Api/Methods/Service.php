<?php

namespace Eviger\Api\Methods;

use Eviger\Api\Tools\Other;
use Eviger\Database;

class Service
{
    /**
     * @return string
     */
    public static function getUpdates(): string {

        return Other::generateJson(
            ["response" => [
                "version" => Database::getInstance()->query("SELECT version FROM eviger.eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['version'],
                "download_link" => "https://".explode("/var/www/", Database::getInstance()->query("SELECT dl FROM eviger.eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['dl'])[1],
                "changelog" => Database::getInstance()->query("SELECT changelog FROM eviger.eviger_updates ORDER BY id DESC LIMIT 1")->fetchAssoc()['changelog']
                ]
            ]
        );

    }
}