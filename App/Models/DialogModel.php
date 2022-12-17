<?php declare(strict_types=1);

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "dialogs")]
class DialogModel extends Model
{
    #[Column(type: Types::INTEGER)]
    private int $firstId;
    #[Column(type: Types::INTEGER)]
    private int $secondId;
    #[Column(type: Types::INTEGER)]
    private int $lastMessageId;
    #[Column(type: Types::TEXT)]
    private string $lastMessageText;
    #[Column(type: Types::INTEGER)]
    private int $lastMessageDate;

    /**
     * @return int
     */
    public function getFirstId(): int
    {
        return $this->firstId;
    }

    /**
     * @param int $firstId
     */
    public function setFirstId(int $firstId): void
    {
        $this->firstId = $firstId;
    }

    /**
     * @return int
     */
    public function getSecondId(): int
    {
        return $this->secondId;
    }

    /**
     * @param int $secondId
     */
    public function setSecondId(int $secondId): void
    {
        $this->secondId = $secondId;
    }

    /**
     * @return int
     */
    public function getLastMessageId(): int
    {
        return $this->lastMessageId;
    }

    /**
     * @param int $lastMessageId
     */
    public function setLastMessageId(int $lastMessageId): void
    {
        $this->lastMessageId = $lastMessageId;
    }

    /**
     * @return string
     */
    public function getLastMessageText(): string
    {
        return $this->lastMessageText;
    }

    /**
     * @param string $lastMessageText
     */
    public function setLastMessageText(string $lastMessageText): void
    {
        $this->lastMessageText = $lastMessageText;
    }

    /**
     * @return int
     */
    public function getLastMessageDate(): int
    {
        return $this->lastMessageDate;
    }

    /**
     * @param int $lastMessageDate
     */
    public function setLastMessageDate(int $lastMessageDate): void
    {
        $this->lastMessageDate = $lastMessageDate;
    }
}