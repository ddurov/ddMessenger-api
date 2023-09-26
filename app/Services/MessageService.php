<?php declare(strict_types=1);

namespace Api\Services;

use Api\LongPoll\Models\LongPollModel;
use Api\Models\DialogModel;
use Api\Models\MessageModel;
use Core\Exceptions\EntityException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
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

        $messageId = ($dialog !== null) ? $dialog->getLastMessageId() + 1 : 1;

        $newMessage = new MessageModel();
        $newMessage->setSenderAId($me["aId"]);
        $newMessage->setPeerAId($aId);
        $newMessage->setMessageId($messageId);
        $newMessage->setMessageText(self::encryptMessage($text));
        $newMessage->setMessageDate($time);

        if ($dialog !== null) {
            $dialog->setLastMessageId($messageId);
            $dialog->setLastMessageText(self::encryptMessage($text));
            $dialog->setLastMessageDate($time);
            $newMessage->setDialogId($dialog->getId());
        } else {
            $newDialog = new DialogModel();
            $newDialog->setFirstId($me["aId"]);
            $newDialog->setSecondId($aId);
            $newDialog->setLastMessageId($messageId);
            $newDialog->setLastMessageText(self::encryptMessage($text));
            $newDialog->setLastMessageDate($time);
            $this->entityManager->persist($newDialog);
            $this->entityManager->flush();
            $newMessage->setDialogId($newDialog->getId());
        }

        $this->entityManager->persist($newMessage);
        $this->entityManager->flush();

        $newEvent = new LongPollModel();
        $newEvent->setAId($me["aId"]);
        $newEvent->setData([
            "type" => "newMessage",
            "data" => [
                "id" => $messageId,
                "peerAId" => $aId,
                "message" => self::encryptMessage($text),
                "messageDate" => $time
            ]
        ]);
        $this->entityManager->persist($newEvent);
        $this->entityManager->flush();

        return $messageId;
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
                "id" => $message->getMessageId(),
                "out" => $message->getSenderAId() === $me["aId"],
                "peerAId" => $message->getPeerAId(),
                "message" => self::decryptMessage($message->getMessageText()),
                "messageDate" => $message->getMessageDate()
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
            $preparedData[] = [
                "peerAId" => $dialog->getFirstId() === $me["aId"] ? $dialog->getSecondId() : $dialog->getFirstId(),
                "lastMessageId" => $dialog->getLastMessageId(),
                "lastMessage" => self::decryptMessage($dialog->getLastMessageText()),
                "lastMessageDate" => $dialog->getLastMessageDate()
            ];
        }

        return $preparedData;
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
        $iv = substr($fullRawDecrypted, 0, $ivLength);
        $rawDecrypted = substr($fullRawDecrypted, $ivLength + 32);
        return openssl_decrypt(
            $rawDecrypted,
            $cipher,
            getenv("MESSAGES_ENCRYPTION_KEY"),
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}