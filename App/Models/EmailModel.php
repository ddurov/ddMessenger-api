<?php

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "email")]
class EmailModel extends Model
{
    #[Column(type: Types::TEXT)]
    private string $code;
    #[Column(type: Types::TEXT)]
    private string $email;
    #[Column(type: Types::INTEGER)]
    private int $requestTime;
    #[Column(type: Types::TEXT)]
    private string $requestIP;
    #[Column(type: Types::TEXT)]
    private string $hash;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getRequestTime(): int
    {
        return $this->requestTime;
    }

    /**
     * @param int $requestTime
     */
    public function setRequestTime(int $requestTime): void
    {
        $this->requestTime = $requestTime;
    }

    /**
     * @return string
     */
    public function getRequestIP(): string
    {
        return $this->requestIP;
    }

    /**
     * @param string $requestIP
     */
    public function setRequestIP(string $requestIP): void
    {
        $this->requestIP = $requestIP;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }
}