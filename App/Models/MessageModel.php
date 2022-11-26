<?php

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
    private int $dialogId;
    #[Column(type: Types::INTEGER)]
    private int $senderAId;
    #[Column(type: Types::INTEGER)]
    private int $peerAId;
    #[Column(type: Types::INTEGER)]
    private int $messageId;
    #[Column(type: Types::TEXT)]
    private string $messageText;
    #[Column(type: Types::INTEGER)]
    private int $messageDate;

    /**
     * @return int
     */
    public function getDialogId(): int
    {
        return $this->dialogId;
    }

    /**
     * @param int $dialogId
     */
    public function setDialogId(int $dialogId): void
    {
        $this->dialogId = $dialogId;
    }

    /**
     * @return int
     */
    public function getSenderAId(): int
    {
        return $this->senderAId;
    }

    /**
     * @param int $senderAId
     */
    public function setSenderAId(int $senderAId): void
    {
        $this->senderAId = $senderAId;
    }

    /**
     * @return int
     */
    public function getPeerAId(): int
    {
        return $this->peerAId;
    }

    /**
     * @param int $peerAId
     */
    public function setPeerAId(int $peerAId): void
    {
        $this->peerAId = $peerAId;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {
        return $this->messageId;
    }

    /**
     * @param int $messageId
     */
    public function setMessageId(int $messageId): void
    {
        $this->messageId = $messageId;
    }

    /**
     * @return string
     */
    public function getMessageText(): string
    {
        return $this->messageText;
    }

    /**
     * @param string $messageText
     */
    public function setMessageText(string $messageText): void
    {
        $this->messageText = $messageText;
    }

    /**
     * @return int
     */
    public function getMessageDate(): int
    {
        return $this->messageDate;
    }

    /**
     * @param int $messageDate
     */
    public function setMessageDate(int $messageDate): void
    {
        $this->messageDate = $messageDate;
    }
}