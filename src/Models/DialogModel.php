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
	private int $localMessageId;
	#[Column(type: Types::INTEGER)]
	private int $firstId;
	#[Column(type: Types::INTEGER)]
	private int $secondId;
	#[Column(type: Types::INTEGER)]
	private int $senderAId;
	#[Column(type: Types::TEXT)]
	private string $text;
	#[Column(type: Types::INTEGER)]
	private int $date;

	/**
	 * @param int $localMessageId
	 * @param int $firstId
	 * @param int $secondId
	 * @param int $senderAId
	 * @param string $text
	 * @param int $date
	 */
	public function __construct(
		int    $localMessageId,
		int    $firstId,
		int    $secondId,
		int    $senderAId,
		string $text,
		int    $date
	)
	{
		$this->localMessageId = $localMessageId;
		$this->firstId = $firstId;
		$this->secondId = $secondId;
		$this->senderAId = $senderAId;
		$this->text = $text;
		$this->date = $date;
	}

	/**
	 * @return int
	 */
	public function getLocalMessageId(): int
	{
		return $this->localMessageId;
	}

	/**
	 * @param int $localMessageId
	 */
	public function setLocalMessageId(int $localMessageId): void
	{
		$this->localMessageId = $localMessageId;
	}

	/**
	 * @return int
	 */
	public function getFirstId(): int
	{
		return $this->firstId;
	}

	/**
	 * @return int
	 */
	public function getSecondId(): int
	{
		return $this->secondId;
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
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}

	/**
	 * @param string $text
	 */
	public function setText(string $text): void
	{
		$this->text = $text;
	}

	/**
	 * @return int
	 */
	public function getDate(): int
	{
		return $this->date;
	}

	/**
	 * @param int $date
	 */
	public function setDate(int $date): void
	{
		$this->date = $date;
	}
}