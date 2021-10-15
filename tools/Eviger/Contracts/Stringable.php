<?php
declare(strict_types=1);

namespace Eviger\Contracts;

interface Stringable
{
    public function __toString(): string;
}