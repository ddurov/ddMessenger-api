<?php
declare(strict_types=1);

namespace Eviger\Api\DTO\Status;

use Eviger\Contracts\StatusInterface;

class Success implements StatusInterface
{
    private ?string $message;
    private int $code;

    public function __construct(string $message = null, int $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }


    public function __toString(): string
    {
        return $this->getMessage();
    }
}