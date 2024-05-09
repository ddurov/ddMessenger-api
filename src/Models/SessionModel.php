<?php declare(strict_types=1);

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
	 * @param string $sessionId
	 * @param int $aId
	 * @param int $authTime
	 * @param int $authDevice
	 * @param string $authIP
	 */
	public function __construct(
		string $sessionId,
		int    $aId,
		int    $authTime,
		int    $authDevice,
		string $authIP
	)
	{
		$this->sessionId = $sessionId;
		$this->aId = $aId;
		$this->authTime = $authTime;
		$this->authDevice = $authDevice;
		$this->authIP = $authIP;
	}

	/**
	 * @return string
	 */
	public function getSessionId(): string
	{
		return $this->sessionId;
	}

	/**
	 * @return int
	 */
	public function getAId(): int
	{
		return $this->aId;
	}

	/**
	 * @return int
	 */
	public function getAuthTime(): int
	{
		return $this->authTime;
	}

	/**
	 * @return int
	 */
	public function getAuthDevice(): int
	{
		return $this->authDevice;
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