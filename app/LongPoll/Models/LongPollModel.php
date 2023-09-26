<?php

namespace Api\LongPoll\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "longpoll")]
class LongPollModel extends Model
{
    #[Column(type: Types::INTEGER)]
    private int $aId;
    #[Column(type: Types::BOOLEAN)]
    private bool $checked;
    #[Column(type: Types::JSON)]
    private array $data;

    public function __construct()
    {
        $this->checked = false;
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
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }

    /**
     * @param bool $checked
     */
    public function setChecked(bool $checked): void
    {
        $this->checked = $checked;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}