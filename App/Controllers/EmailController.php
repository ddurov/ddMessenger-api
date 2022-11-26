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
use Doctrine\ORM\OptimisticLockException;
use Rakit\Validation\Validator;

class EmailController extends Controller
{
    /**
     * @return void
     * @throws Exception
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws InternalError
     */
    public function createCode(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"], [
            "email" => "required|email"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new Response())->setResponse(["hash" =>
            (new EmailService(Database::getInstance(), Mailer::getInstance()))->createCode(parent::$inputData["data"]["email"])
        ])->send();
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidParameter
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \PHPMailer\PHPMailer\Exception
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
            (new EmailService(Database::getInstance(), Mailer::getInstance()))->confirmCode(
                parent::$inputData["data"]["code"],
                parent::$inputData["data"]["hash"],
                (int) parent::$inputData["data"]["needRemove"]
            )
        ])->send();
    }
}