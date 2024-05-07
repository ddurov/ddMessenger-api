<?php

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "long_poll")]
class LongPollModel extends Model
{
    #[Column(type: Types::STRING)]
    private string $aIds;
    #[Column(type: Types::JSON)]
    private array $data;

    /**
     * @param array $aIds
     * @param array $data
     */
    public function __construct(
        array $aIds,
        array $data
    ) {
        $this->aIds = "|".implode("|", $aIds)."|";;
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getAIds(): array
    {
        preg_match_all("|\d+|", $this->aIds, $matches);
        return $matches[0];
    }

    /**
     * @param array $aIds
     */
    public function setAIds(array $aIds): void
    {
        $this->aIds = "|".implode("|", $aIds)."|";
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}