<?php declare(strict_types=1);

namespace Api\Services;

use Api\Models\DialogModel;
use Api\Models\LongPollModel;
use Api\Models\MessageModel;
use Api\Models\TokenModel;
use Core\Exceptions\EntityException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\Parameter;

class MessageService
{
	private EntityRepository $entityRepository;
	private EntityManager $entityManager;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		$this->entityRepository = $entityManager->getRepository(MessageModel::class);
	}

	/**
	 * Возвращает id нового сообщения
	 * @param int $aId
	 * @param string $text
	 * @param string $token
	 * @return int
	 * @throws ORMException|EntityException
	 */
	public function send(int $aId, string $text, string $token): int
	{
		$me = (new UserService($this->entityManager))->get(null, $token);

		/** @var DialogModel $dialog */
		$dialog = $this->entityManager->getRepository(DialogModel::class)->createQueryBuilder("d")
			->where("d.firstId = :fId AND d.secondId = :sId")
			->orWhere("d.firstId = :sId AND d.secondId = :fId")
			->setParameters(new ArrayCollection([
				new Parameter("fId", $me["aId"]),
				new Parameter("sId", $aId)
			]))
			->getQuery()->getOneOrNullResult();

		$time = time();

		$localMessageId = ($dialog !== null) ? $dialog->getLocalMessageId() + 1 : 1;

		if ($dialog !== null) {
			$dialog->setSenderAId($me["aId"]);
			$dialog->setLocalMessageId($localMessageId);
			$dialog->setText(self::encryptMessage($text));
			$dialog->setDate($time);
		} else {
			$dialog = new DialogModel(
				$localMessageId,
				$me["aId"],
				$aId,
				$me["aId"],
				self::encryptMessage($text),
				$time
			);
		}

		$this->entityManager->persist($dialog);
		$this->entityManager->flush();

		$this->entityManager->persist(new MessageModel(
			$localMessageId,
			$dialog->getId(),
			$aId,
			$me["aId"],
			self::encryptMessage($text),
			$time
		));
		$this->entityManager->persist(new LongPollModel(
			[$aId, $me["aId"]],
			[
				"type" => "newMessage",
				"data" => [
					"id" => $localMessageId,
					"peerAId" => $aId,
					"senderAId" => $me["aId"],
					"text" => self::encryptMessage($text),
					"time" => $time
				]
			]
		));
		$this->entityManager->flush();

		return $localMessageId;
	}

	/**
	 * Возвращает историю диалога
	 * @param int $aId
	 * @param int|null $offset
	 * @param string $token
	 * @return array
	 * @throws ORMException|EntityException
	 */
	public function getHistory(int $aId, ?int $offset, string $token): array
	{
		$me = (new UserService($this->entityManager))->get(null, $token);

		/** @var DialogModel $dialog */
		$dialog = $this->entityManager->getRepository(DialogModel::class)->createQueryBuilder("d")
			->where("d.firstId = :fId AND d.secondId = :sId")
			->orWhere("d.firstId = :sId AND d.secondId = :fId")
			->setParameters(new ArrayCollection([
				new Parameter("fId", $me["aId"]),
				new Parameter("sId", $aId)
			]))
			->getQuery()->getOneOrNullResult();

		/** @var MessageModel[] $messages */
		$messages = $this->entityRepository->createQueryBuilder("m")
			->where("m.dialogId = :id")
			->setParameter("id", $dialog->getId())
			->setFirstResult($offset)
			->getQuery()->getResult();

		$preparedData = [];

		foreach ($messages as $message) {
			$preparedData[] = [
				"id" => $message->getLocalMessageId(),
				"peerAId" => $message->getPeerAId(),
				"senderAId" => $message->getSenderAId(),
				"text" => self::decryptMessage($message->getText()),
				"time" => $message->getTime()
			];
		}

		return $preparedData;
	}

	/**
	 * Возвращает список диалогов
	 * @param string $token
	 * @return array
	 * @throws ORMException|EntityException
	 */
	public function getDialogs(string $token): array
	{
		$me = (new UserService($this->entityManager))->get(null, $token);

		/** @var DialogModel[] $dialogs */
		$dialogs = $this->entityManager->getRepository(DialogModel::class)->createQueryBuilder("d")
			->where("d.firstId = :id OR d.secondId = :id")
			->setParameter("id", $me["aId"])
			->getQuery()->getResult();

		$preparedData = [];

		foreach ($dialogs as $dialog) {
			$peerAId = $dialog->getFirstId() === $me["aId"] ? $dialog->getSecondId() : $dialog->getFirstId();

			$preparedData[] = [
				"peerAId" => $peerAId,
				"senderAId" => $dialog->getSenderAId(),
				"messageId" => $dialog->getLocalMessageId(),
				"text" => self::decryptMessage($dialog->getText()),
				"time" => $dialog->getDate()
			];
		}

		return $preparedData;
	}

	/**
	 * Прослушивание новых событий
	 * @param int $timeout
	 * @param string $token
	 * @return array
	 * @throws NotSupported
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function getUpdates(int $timeout, string $token): array
	{
		$me = $this->entityManager->getRepository(TokenModel::class)->findOneBy(
			["token" => $token]
		)->getAId();

		for ($i = 0; $i < $timeout; $i++) {
			/** @var LongPollModel[] $events */
			$events = $this->entityManager->getRepository(LongPollModel::class)->createQueryBuilder("e")
				->where("e.aIds LIKE :exp")
				->setParameter("exp", "%|$me|%")
				->getQuery()
				->getResult();

			if ($events === []) {
				sleep(1);
				continue;
			}

			$preparedData = [];

			foreach ($events as $event) {
				$event->setAIds(array_diff($event->getAIds(), [$me]));
				switch ($event->getData()["type"]) {
					case "newMessage":
						$preparedData[] = $this->changeValue(
							"text",
							MessageService::decryptMessage($event->getData()["data"]["text"]),
							$event->getData()
						);
						break;
					default:
						$preparedData[] = $event->getData();
						break;
				}

				$this->entityManager->flush();
			}

			return $preparedData;
		}
		return [];
	}

	private function changeValue($findKey, $newValue, $array): array
	{
		$newArray = [];
		foreach ($array as $key => $value) {
			$newArray[$key] = $value;
			if ($findKey === $key) $newArray[$key] = $newValue;
			if (is_array($value)) $newArray[$key] = $this->changeValue($findKey, $newValue, $value);
		}
		return $newArray;
	}

	/**
	 * @param string $message
	 * @return string
	 */
	public function encryptMessage(string $message): string
	{
		$iv = openssl_random_pseudo_bytes(
			openssl_cipher_iv_length($cipher = 'aes-256-ctr')
		);
		$rawEncrypted = openssl_encrypt(
			$message,
			$cipher,
			getenv("MESSAGES_ENCRYPTION_KEY"),
			OPENSSL_RAW_DATA,
			$iv);
		$hmac = hash_hmac(
			'sha256',
			$rawEncrypted,
			getenv("MESSAGES_ENCRYPTION_KEY"),
			true
		);
		return base64_encode($iv . $hmac . $rawEncrypted);
	}

	/**
	 * @param string $messageEncoded
	 * @return string
	 */
	public static function decryptMessage(string $messageEncoded): string
	{
		$fullRawDecrypted = base64_decode($messageEncoded);
		$ivLength = openssl_cipher_iv_length($cipher = 'aes-256-ctr');
		return openssl_decrypt(
			substr($fullRawDecrypted, $ivLength + 32),
			$cipher,
			getenv("MESSAGES_ENCRYPTION_KEY"),
			OPENSSL_RAW_DATA,
			substr($fullRawDecrypted, 0, $ivLength)
		);
	}
}