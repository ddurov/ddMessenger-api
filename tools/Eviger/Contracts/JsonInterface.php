<?php
declare(strict_types=1);

namespace Eviger\Contracts;

interface JsonInterface
{
    public function toJson(): string;
}