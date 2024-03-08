<?php declare(strict_types=1);

namespace Api\Services;

use Api\Models\TokenModel;
use Api\Singleton\Database;
use Core\Exceptions\EntityException;
use Core\Exceptions\ParametersException;
use Api\Models\UserModel;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;

class UserService
{
    private EntityRepository $entityRepository;
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(UserModel::class);
    }

    /**
     * Регистрирует пользователя, возвращает id
     * @param string $login
     * @param string $password
     * @param string $username
     * @param string $email
     * @return int
     * @throws ORMException|EntityException
     */
    public function register(string $login, string $password, string $username, string $email): int
    {
        if ($this->entityRepository->findOneBy(["login" => $login]) !== null)
            throw new EntityException("current entity 'account by login' are exists", 422);

        if ($this->entityRepository->findOneBy(["username" => $username]) !== null)
            throw new EntityException("current entity 'account by username' are exists", 422);

        $newUser = new UserModel(
            $login,
            $password,
            bin2hex(openssl_random_pseudo_bytes(16)),
            $email,
            $username
        );

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        return $newUser->getId();
    }

    /**
     * Авторизует аккаунт, возвращает сессию
     * * TODO: Перехуярить логику блокировок в админ функции и переписать проверку бана
     * @param string $login
     * @param string $password
     * @return string
     * @throws ORMException
     * @throws EntityException
     * @throws ParametersException
     * @throws Exception
     */
    public function auth(string $login, string $password): string
    {
        /** @var UserModel $account */
        $account = $this->entityRepository->findOneBy(["login" => $login]);

        if ($account === null)
            throw new EntityException("current entity 'account by login' not found", 404);

        if (md5($password . $account->getPasswordSalt()) !== $account->getPassword())
            throw new ParametersException("parameter 'password' are invalid");

        /*
        $ban = Database::getInstance()->query("SELECT * FROM general.bans WHERE eid = ?i", $accountAsArray['id']);
        $banAsArray = $ban->fetchAssoc();

        if ($ban->getNumRows()) throw new selfThrows(["message" => "account has banned", "details" => ["reason" => $banAsArray["reason"], "canRestoreAccount" => (time() > $banAsArray['unbanTime'])]], 500);

        if (Database::getInstance()->query("SELECT * FROM general.attempts_auth WHERE login = '?s'", $login)->getNumRows() >= 5) {

            $banAsArray = Database::getInstance()->query("SELECT * FROM general.bans WHERE eid = ?i", $accountAsArray['id'])->fetchAssoc();
            throw new selfThrows(["message" => "account has banned", "details" => ["reason" => $banAsArray["reason"], "canRestoreAccount" => (time() > $banAsArray['unbanTime'])]], 500);

        }
        */

        /*Database::getInstance()->query("INSERT INTO auth_attempts (aid, `time`, authIp) VALUES (?i, ?i, '?s')",
            $accountAsArray['id'],
            time(),
            $_SERVER['REMOTE_ADDR']);*/

        return (new SessionService(Database::getInstance()))->createByAId($account->getId());
    }

    /**
     * Изменяет пароль аккаунта, возвращает true
     * * TODO: Сделать удаление всех авторизованных сессий и токенов
     * @param string $login
     * @param string $newPassword
     * @return void
     * @throws ORMException
     */
    public function resetPassword(string $login, string $newPassword): void
    {
        $salt = bin2hex(openssl_random_pseudo_bytes(16));

        /** @var UserModel $account */
        $account = $this->entityRepository->findOneBy(["login" => $login]);

        $account->setPassword($newPassword, $salt);
        $account->setPasswordSalt($salt);

        $this->entityManager->flush();
    }

    /**
     * Изменяет имя пользователя
     * @param string $newName
     * @param string $token
     * @return void
     * @throws ORMException|ParametersException|EntityException
     */
    public function changeName(string $newName, string $token): void
    {
        /** @var UserModel $account */
        $account = $this->entityRepository->find((new UserService($this->entityManager))->get(null, $token)["aId"]);

        if ($account->getUsername() === $newName)
            throw new ParametersException("newName hasn't been changed");

        $account->setUsername($newName);
    }

    /**
     * Возвращает информацию об пользователе по айди
     * @param int|null $aId
     * @param string $token
     * @return array
     * @throws EntityException|NotSupported
     */
    public function get(?int $aId, string $token): array
    {
        /** @var UserModel $account */
        $account = $this->entityRepository->find(
            $aId ?? $this->entityManager->getRepository(TokenModel::class)->findOneBy(
                ["token" => $token]
            )->getAId()
        );

        if ($account === null) throw new EntityException("current entity 'account by id' not found", 404);

        return [
            "aId" => $account->getId(),
            "username" => $account->getUsername()
        ];
    }

    /**
     * Возвращает массив информации найденных пользователей по поисковому запросу
     * @param string $query
     * @return array
     * @throws EntityException
     */
    public function search(string $query): array
    {
        /** @var UserModel[] $accounts */
        $accounts = $this->entityRepository->createQueryBuilder("u")
            ->where("u.username LIKE :search")
            ->setParameter("search", "%$query%")
            ->getQuery()->getResult();

        if ($accounts === [])
            throw new EntityException("current entities 'accounts by search' not found", 404);

        $preparedData = [];

        foreach ($accounts as $account) {
            $preparedData[] = [
                "aId" => $account->getId(),
                "username" => $account->getUsername(),
            ];
        }

        return $preparedData;
    }
}