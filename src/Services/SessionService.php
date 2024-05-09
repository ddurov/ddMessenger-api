<?php declare(strict_types=1);

namespace Api\Services;

use Api\Models\SessionModel;
use Api\Models\TokenModel;
use Core\Exceptions\EntityException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class SessionService
{
	private EntityRepository $entityRepository;
	private EntityManager $entityManager;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		$this->entityRepository = $entityManager->getRepository(SessionModel::class);
	}

	/**
	 * Возвращает новую сессию
	 * @param string $token
	 * @return string
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function create(string $token): string
	{
		return $this->_create(
			$this->entityManager->getRepository(TokenModel::class)->findOneBy(
				["token" => $token]
			)->getAId()
		);
	}

	/**
	 * Возвращает новую сессию по aId (использование ИСКЛЮЧИТЕЛЬНО только для внутренней работы API)
	 * @param int $aId
	 * @return string
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function createByAId(int $aId): string
	{
		return $this->_create($aId);
	}

	/**
	 * @param int $aId
	 * @return string
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	private function _create(int $aId): string
	{
		$this->entityManager->persist(new SessionModel(
			$sessionId = bin2hex(openssl_random_pseudo_bytes(32)),
			$aId,
			time(),
			(preg_match("/dd(.*)App/m", $_SERVER["HTTP_USER_AGENT"])) ? 1 : 0,
			$_SERVER['REMOTE_ADDR']
		));
		$this->entityManager->flush();

		return $sessionId;
	}

	/**
	 * Возвращает 1 сессию
	 * @param string $token
	 * @return string
	 * @throws NotSupported
	 */
	public function get(string $token): string
	{
		return $this->entityRepository->findOneBy(["aId" =>
			$this->entityManager->getRepository(TokenModel::class)->findOneBy(
				["token" => $token]
			)->getAId()
		])->getSessionId();
	}

	/**
	 * Возвращает true в случае успешной проверки, выбрасывает исключение в ином
	 * @param string $sessionId
	 * @return bool
	 * @throws EntityException
	 */
	public function check(string $sessionId): bool
	{
		if ($this->entityRepository->findOneBy(["sessionId" => $sessionId]) === null)
			throw new EntityException("current entity 'session by id' not found", 404);

		return true;
	}
}