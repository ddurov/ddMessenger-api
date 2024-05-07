<?php declare(strict_types=1);

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "messages")]
class MessageModel extends Model
{
    #[Column(type: Types::INTEGER)]
    private int $localMessageId;
    #[Column(type: Types::INTEGER)]
    private int $dialogId;
    #[Column(type: Types::INTEGER)]
    private int $peerAId;
    #[Column(type: Types::INTEGER)]
    private int $senderAId;
    #[Column(type: Types::TEXT)]
    private string $text;
    #[Column(type: Types::INTEGER)]
    private int $time;

    /**
     * @param int $localMessageId
     * @param int $dialogId
     * @param int $peerAId
     * @param int $senderAId
     * @param string $text
     * @param int $time
     */
    public function __construct(
        int $localMessageId,
        int $dialogId,
        int $peerAId,
        int $senderAId,
        string $text,
        int $time
    ) {
        $this->localMessageId = $localMessageId;
        $this->dialogId = $dialogId;
        $this->peerAId = $peerAId;
        $this->senderAId = $senderAId;
        $this->text = $text;
        $this->time = $time;
    }

    /**
     * @return int
     */
    public function getLocalMessageId(): int
    {
        return $this->localMessageId;
    }

    /**
     * @return int
     */
    public function getDialogId(): int
    {
        return $this->dialogId;
    }

    /**
     * @return int
     */
    public function getPeerAId(): int
    {
        return $this->peerAId;
    }

    /**
     * @return int
     */
    public function getSenderAId(): int
    {
        return $this->senderAId;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }
}