<?php declare(strict_types=1);

namespace Api\Services;

use Core\Exceptions\InternalError;
use Core\Exceptions\InvalidParameter;
use Api\Models\EmailModel;
use Core\Tools\Other;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    private EntityManager $entityManager;
    private EntityRepository $entityRepository;
    private PHPMailer $mailer;

    public function __construct(EntityManager $entityManager, PHPMailer $mailer)
    {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(EmailModel::class);
    }

    /**
     * Возвращает хэш кода подтверждения
     * @param string $email
     * @return string
     * @throws ORMException
     * @throws Exception
     * @throws InternalError
     */
    public function createCode(string $email): string
    {
        $code = bin2hex(openssl_random_pseudo_bytes(8));
        $hash = bin2hex(openssl_random_pseudo_bytes(16));

        /** @var EmailModel $emailCodeDetails */
        $emailCodeDetails = $this->entityRepository->findOneBy(["email" => $email]);

        if ($emailCodeDetails !== null) {
            if ((time() - $emailCodeDetails->getRequestTime()) < 300) return $emailCodeDetails->getHash();

            $emailCodeDetails->setCode($code);
            $emailCodeDetails->setRequestTime(time());
            $emailCodeDetails->setHash($hash);
        } else {
            $newEmailCode = new EmailModel();
            $newEmailCode->setCode($code);
            $newEmailCode->setEmail($email);
            $newEmailCode->setRequestTime(time());
            $newEmailCode->setRequestIP($_SERVER['REMOTE_ADDR']);
            $newEmailCode->setHash($hash);

            $this->entityManager->persist($newEmailCode);
        }

        $this->mailer->addAddress($email);
        $this->mailer->isHTML();
        $this->mailer->Subject = "Код подтверждения";
        $this->mailer->Body = "Код подтверждения: <b>$code</b><br>Данный код будет активен в течение часа с момента получения письма<br><b>Если Вы не запрашивали данное письмо — немедленно смените пароль</b>";
        if (!$this->mailer->send()) {
            Other::log($this->mailer->ErrorInfo);
            throw new InternalError("mail hasn't been sent, internal error");
        }

        $this->entityManager->flush();

        return $hash;
    }

    /**
     * Возвращает true в случае успешной проверки, выбрасывает исключение если неуспешно
     * @param string $code
     * @param string $hash
     * @param int $needRemove
     * @return bool
     * @throws InvalidParameter
     * @throws ORMException
     */
    public function confirmCode(string $code, string $hash, int $needRemove = 0): bool
    {
        /** @var EmailModel $codeDetails */
        $codeDetails = $this->entityRepository->findOneBy(["code" => $code, "hash" => $hash]);

        if ($codeDetails === null || $codeDetails->getCode() !== $code) throw new InvalidParameter("parameter 'code' are invalid");

        if ($codeDetails->getHash() !== $hash) throw new InvalidParameter("parameter 'hash' are invalid");

        if ($needRemove === 1) $this->entityManager->remove($codeDetails);

        $this->entityManager->flush();

        return true;
    }
}