<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Singletones\Database;
use Api\Singletones\Mailer;
use Core\Controllers\Controller;
use Core\DTO\Response;
use Core\Exceptions\InternalError;
use Core\Exceptions\InvalidParameter;
use Api\Services\EmailService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Rakit\Validation\Validator;

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
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function createCode(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"], [
            "email" => "required|email"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new Response())->setResponse(["hash" =>
            $this->emailService->createCode(parent::$inputData["data"]["email"])
        ])->send();
    }

    /**
     * @return void
     * @throws InvalidParameter
     * @throws ORMException
     */
    public function confirmCode(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"], [
            "code" => "required",
            "hash" => "required",
            "needRemove" => "required|numeric"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new Response())->setResponse(["valid" =>
            $this->emailService->confirmCode(
                parent::$inputData["data"]["code"],
                parent::$inputData["data"]["hash"],
                (int) parent::$inputData["data"]["needRemove"]
            )
        ])->send();
    }
}