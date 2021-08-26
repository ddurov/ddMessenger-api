<?php

namespace Eviger\Api\Methods;

use Eviger\Database;

class Email
{
    public function createCode()
    {
        Database::getInstance()->query("INSERT INTO ....");
    }

    public function confirmCode(/* params... */)
    {
        Database::getInstance()->query("INSERT INTO ....");
    }
}