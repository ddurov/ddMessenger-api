<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Singletones\Database;
use Api\Singletones\Mailer;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\InternalError;
use Core\Exceptions\ParametersException;
use Api\Services\EmailService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;

class EmailController extends Controller
{
    private EmailService $emailService;

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws ORMException
     * @throws Exception
     */
    public function __construct()
    {
        $this->emailService = new EmailService(Database::getInstance(), Mailer::getInstance());
        parent::__construct();
    }

    /**
     * @return void
     * @throws InternalError
     * @throws ORMException
     * @throws \PHPMailer\PHPMailer\Exception|ParametersException
     */
    public function createCode(): void
    {
        parent::validateData(parent::$inputData["data"], [
            "email" => "required|email"
        ]);

        (new SuccessResponse())->setBody(
            $this->emailService->createCode(
                parent::$inputData["data"]["email"]
            )
        )->send();
    }

    /**
     * @return void
     * @throws ORMException|ParametersException
     */
    public function confirmCode(): void
    {
        parent::validateData(parent::$inputData["data"], [
            "code" => "required",
            "hash" => "required",
            "needRemove" => "required|numeric"
        ]);

        (new SuccessResponse())->setBody(
            $this->emailService->confirmCode(
                parent::$inputData["data"]["code"],
                parent::$inputData["data"]["hash"],
                (int) parent::$inputData["data"]["needRemove"]
            )
        )->send();
    }
}