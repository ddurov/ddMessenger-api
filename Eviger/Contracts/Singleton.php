<?php

namespace Eviger\Contracts;

interface Singleton
{
    /**
     * @return static
     */
    public static function getInstance();
}