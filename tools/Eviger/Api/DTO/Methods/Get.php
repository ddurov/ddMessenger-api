<?php
declare(strict_types=1);

namespace Eviger\Api\DTO;

use Eviger\Contracts\ArrayInterface;
use Eviger\Contracts\JsonInterface;

class Get implements ArrayInterface, JsonInterface
{

    private int $eid;
    private string $username;
    private int $lastSeen;

    //optional parameters, or if access is from someone else's token
    private ?string $email = null;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Get
     */
    public function setUsername(string $username): Get
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastSeen(): ?int
    {
        return $this->lastSeen;
    }

    /**
     * @param int $lastSeen
     * @return Get
     */
    public function setLastSeen(int $lastSeen): Get
    {
        $this->lastSeen = $lastSeen;
        return $this;
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
     * @return Get
     */
    public function setEmail(string $email): Get
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getEid(): int
    {
        return $this->eid;
    }
    /**
     * @param int $eid
     * @return Get
     */
    public function setEid(int $eid): Get
    {
        $this->eid = $eid;
        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}