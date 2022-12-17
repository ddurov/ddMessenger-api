<?php declare(strict_types=1);

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "tokens")]
class TokenModel extends Model
{
    #[Column(type: Types::INTEGER)]
    private int $aId;
    #[Column(type: Types::TEXT)]
    private string $token;
    #[Column(type: Types::INTEGER)]
    private int $tokenType;

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
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getTokenType(): int
    {
        return $this->tokenType;
    }

    /**
     * @param int $tokenType
     */
    public function setTokenType(int $tokenType): void
    {
        $this->tokenType = $tokenType;
    }
}