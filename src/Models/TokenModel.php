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
     * @param int $aId
     * @param string $token
     * @param int $tokenType
     */
    public function __construct(
        int $aId,
        string $token,
        int $tokenType
    ) {
        $this->aId = $aId;
        $this->token = $token;
        $this->tokenType = $tokenType;
    }

    /**
     * @return int
     */
    public function getAId(): int
    {
        return $this->aId;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getTokenType(): int
    {
        return $this->tokenType;
    }
}