<?php declare(strict_types=1);

namespace Api\Contracts;

interface Singleton
{
    /**
     * @return mixed
     */
    public static function getInstance(): mixed;
}