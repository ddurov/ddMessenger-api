<?php declare(strict_types=1);

namespace Api\Services;

use Api\Models\SessionModel;
use Api\Models\TokenModel;
use Core\Exceptions\EntityException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;

class TokenService
{
    private EntityRepository $entityRepository;
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(TokenModel::class);
    }

    /**
     * Возвращает новый токен
     * @param int $tokenType
     * @param string $sessionId
     * @return string
     * @throws ORMException
     */
    public function create(int $tokenType, string $sessionId): string
    {
        $this->entityManager->persist(new TokenModel(
            $this->entityManager->getRepository(SessionModel::class)->findOneBy(
                ["sessionId" => $sessionId]
            )->getAId(),
            $token = bin2hex(openssl_random_pseudo_bytes(48)),
            $tokenType
        ));
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Возвращает 1 токен
     * @param int $tokenType
     * @param string $sessionId
     * @return string
     * @throws NotSupported
     */
    public function get(int $tokenType, string $sessionId): string
    {
        return $this->entityRepository->findOneBy(
            ["aId" =>
                $this->entityManager->getRepository(SessionModel::class)->findOneBy(
                    ["sessionId" => $sessionId]
                )->getAId(),
                "tokenType" => $tokenType
            ])->getToken();
    }

    /**
     * Возвращает true в случае успешной проверки, выбрасывает исключение в ином
     * @param string $token
     * @return bool
     * @throws EntityException
     */
    public function check(string $token): bool
    {
        if ($this->entityRepository->findOneBy(["token" => $token]) === null)
            throw new EntityException("current entity 'token' not found", 404);

        return true;
    }
}