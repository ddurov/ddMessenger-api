<?php
declare(strict_types=1);

namespace Eviger\Api\DTO;

class Response
{
    private int $code = 200;
    private string $status;
    private $response;

    /**
     * @return string
     */
    public function toJson(): string
    {
        http_response_code($this->code);
        unset($this->code);
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @param string $status
     * @return Response
     */
    public function setStatus(string $status): Response
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param array|bool $response
     * @return Response
     */
    public function setResponse($response): Response
    {
        $this->response = $response;
        return $this;
    }

    public function setCode(int $code): Response {
        $this->code = $code;
        return $this;
    }

    /**
     * @return array|bool
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function send(): void
    {
        die(self::toJson());
    }
}