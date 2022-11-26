<?php

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "sessions")]
class SessionModel extends Model
{
    #[Column(type: Types::TEXT)]
    private string $sessionId;
    #[Column(type: Types::INTEGER)]
    private int $aId;
    #[Column(type: Types::INTEGER)]
    private int $authTime;
    #[Column(type: Types::INTEGER)]
    private int $authDevice;
    #[Column(type: Types::TEXT)]
    private string $authIP;

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return int
     */
    public function getAId(): int
    {
        return $this->aId;
    }

    /**
     * @param int $aId
     */
    public function setAId(int $aId): void
    {
        $this->aId = $aId;
    }

    /**
     * @return int
     */
    public function getAuthTime(): int
    {
        return $this->authTime;
    }

    /**
     * @param int $authTime
     */
    public function setAuthTime(int $authTime): void
    {
        $this->authTime = $authTime;
    }

    /**
     * @return int
     */
    public function getAuthDevice(): int
    {
        return $this->authDevice;
    }

    /**
     * @param int $authDevice
     */
    public function setAuthDevice(int $authDevice): void
    {
        $this->authDevice = $authDevice;
    }

    /**
     * @return string
     */
    public function getAuthIP(): string
    {
        return $this->authIP;
    }

    /**
     * @param string $authIP
     */
    public function setAuthIP(string $authIP): void
    {
        $this->authIP = $authIP;
    }
}