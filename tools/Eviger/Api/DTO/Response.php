<?php
declare(strict_types=1);

namespace Eviger\Api\DTO;

use Eviger\Api\DTO\Status\Error;
use Eviger\Contracts\ArrayInterface;
use Eviger\Contracts\JsonInterface;
use Eviger\Contracts\StatusInterface;
use Eviger\Contracts\Stringable;

class Response implements JsonInterface, ArrayInterface, Stringable
{
    private StatusInterface $status;
    private array $response;

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @return StatusInterface
     */
    public function getStatus(): StatusInterface
    {
        return $this->status;
    }

    /**
     * @param StatusInterface $status
     * @return Response
     */
    public function setStatus(StatusInterface $status): Response
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param array|ArrayInterface $response
     * @return Response
     */
    public function setResponse($response): Response
    {
        if ($response instanceof ArrayInterface) {
            $response = $response->toArray();
        }

        $this->response = $response;
        return $this;
    }

    public function toArray(): array
    {
        $status = $this->getStatus()->toArray();
        if (isset($response["response"])) {
            $status["response"] = $this->getResponse();
        }
        return $status;
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function send(): void
    {
        die($this->toJson());
    }
}