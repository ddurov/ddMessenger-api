<?php declare(strict_types=1);

namespace Api\Models;

use Core\Models\Model;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "users")]
class UserModel extends Model
{
	#[Column(type: Types::TEXT)]
	private string $login;
	#[Column(type: Types::TEXT)]
	private string $password;
	#[Column(type: Types::TEXT)]
	private string $passwordSalt;
	#[Column(type: Types::TEXT)]
	private string $email;
	#[Column(type: Types::TEXT)]
	private string $username;
	#[Column(type: Types::INTEGER)]
	private int $isAdmin;

	/**
	 * @param string $login
	 * @param string $password
	 * @param string $passwordSalt
	 * @param string $email
	 * @param string $username
	 * @param int $isAdmin
	 */
	public function __construct(
		string $login,
		string $password,
		string $passwordSalt,
		string $email,
		string $username,
		int    $isAdmin = 0
	)
	{
		$this->login = $login;
		$this->password = md5($password . $passwordSalt);
		$this->passwordSalt = $passwordSalt;
		$this->email = $email;
		$this->username = $username;
		$this->isAdmin = $isAdmin;
	}

	/**
	 * @return string
	 */
	public function getLogin(): string
	{
		return $this->login;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->password;
	}

	/**
	 * @param string $password
	 * @param string $salt
	 */
	public function setPassword(string $password, string $salt): void
	{
		$this->password = md5($password . $salt);
	}

	/**
	 * @return string
	 */
	public function getPasswordSalt(): string
	{
		return $this->passwordSalt;
	}

	/**
	 * @param string $passwordSalt
	 */
	public function setPasswordSalt(string $passwordSalt): void
	{
		$this->passwordSalt = $passwordSalt;
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
	 */
	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getUsername(): string
	{
		return $this->username;
	}

	/**
	 * @param string $username
	 */
	public function setUsername(string $username): void
	{
		$this->username = $username;
	}

	/**
	 * @return int
	 */
	public function getIsAdmin(): int
	{
		return $this->isAdmin;
	}

	/**
	 * @param int $isAdmin
	 */
	public function setIsAdmin(int $isAdmin): void
	{
		$this->isAdmin = $isAdmin;
	}
}