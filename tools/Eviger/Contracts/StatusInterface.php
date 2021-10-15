<?php
declare(strict_types=1);

namespace Eviger\Contracts;

interface StatusInterface extends Stringable, ArrayInterface
{
    public function getMessage(): ?string;

    /**
     * @return int
     */
    public function getCode(): int;
}