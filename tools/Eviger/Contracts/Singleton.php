<?php

declare(strict_types=1);

namespace Eviger\Contracts;

interface Singleton
{
    /**
     * @return static
     */
    public static function getInstance();
}