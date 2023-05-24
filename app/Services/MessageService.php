<?php declare(strict_types=1);

namespace Api\Services;

use Api\Models\DialogModel;
use Api\Models\MessageModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
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
     * @throws ORMException
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

        /* TODO: переделать под орм лонгпул
         *
         * Database::getInstance()->query("INSERT INTO longpoll_data (`to`, `data`, isChecked, `date`) VALUES (?i, '?s', 0, UNIX_TIMESTAMP())",
            $aId,
            serialize([
                "eventType" => "newMessage",
                "eventData" => [
                    "id" => $messageId,
                    "peerId" => $aId,
                    "senderId" => $me["id"],
                    "message" => MessageService::encryptMessage($text),
                    "date" => $time
                ]
            ])
        );*/

        return $messageId;
    }

    /**
     * Возвращает историю диалога
     * @param int $aId
     * @param int|null $offset
     * @param string $token
     * @return array
     * @throws ORMException
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
     * @throws NotSupported
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
    private function encryptMessage(string $message): string
    {
        $ivLength = openssl_cipher_iv_length($cipher = "aes-128-cbc");
        $iv = openssl_random_pseudo_bytes($ivLength);
        $ciphertext_raw = openssl_encrypt(
            $message,
            $cipher,
            getenv("MESSAGES_ENCRYPTION_KEY"),
            OPENSSL_RAW_DATA,
            $iv
        );
        $hmac = hash_hmac('sha256', $ciphertext_raw, getenv("MESSAGES_ENCRYPTION_KEY"), true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    /**
     * @param string $messageEncoded
     * @return string
     */
    private function decryptMessage(string $messageEncoded): string
    {
        $c = base64_decode($messageEncoded);
        $ivLength = openssl_cipher_iv_length($cipher = "aes-128-cbc");
        $iv = substr($c, 0, $ivLength);
        substr($c, $ivLength, $sha2len = 32);
        $cipherTextRaw = substr($c, $ivLength + $sha2len);
        return openssl_decrypt(
            $cipherTextRaw,
            $cipher,
            getenv("MESSAGES_ENCRYPTION_KEY"),
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}