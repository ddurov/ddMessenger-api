<?php
declare(strict_types=1);

namespace Eviger\Api\DTO\Status;


class Error extends Success
{
    public function toArray(): array
    {
        return ["response"];
    }
}