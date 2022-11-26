<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\EmailService;
use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Services\UserService;
use Api\Singletones\Database;
use Api\Singletones\Mailer;
use Core\Controllers\Controller;
use Core\DTO\Response;
use Core\Exceptions\EntityExists;
use Core\Exceptions\EntityNotFound;
use Core\Exceptions\InvalidParameter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Rakit\Validation\Validator;

class UserController extends Controller
{
    /**
     * @return void
     * @throws EntityExists
     * @throws Exception
     * @throws InvalidParameter
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function register(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"], [
            "login" => "required|between:6,20|regex:/\w+/",
            "password" => "required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/",
            "username" => "required",
            "email" => "required|regex:/(.*)@([\w\-\.]+)\.([\w]+)/",
            "emailCode" => "required",
            "hash" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        if (preg_match("/^a?id\d+/", parent::$inputData["data"]["username"]))
            throw new InvalidParameter("username shouldn't contains (a)id prefix");

        (new EmailService(Database::getInstance(), Mailer::getInstance()))->confirmCode(parent::$inputData["data"]["emailCode"], parent::$inputData["data"]["hash"], 1);

        (new Response())->setResponse(["aId" => (new UserService(Database::getInstance()))->register(
            parent::$inputData["data"]["login"],
            parent::$inputData["data"]["password"],
            parent::$inputData["data"]["username"],
            parent::$inputData["data"]["email"],
        )])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws InvalidParameter
     * @throws ORMException
     */
    public function auth(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"], [
            "login" => "required|between:6,20|regex:/\w+/",
            "password" => "required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new Response())->setResponse(["sessionId" => (new UserService(Database::getInstance()))->auth(
            parent::$inputData["data"]["login"],
            parent::$inputData["data"]["password"]
        )])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws InvalidParameter
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function resetPassword(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "newPassword" => "required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/",
            "emailCode" => "required",
            "hash" => "required",
            "HTTP_SESSION_ID" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new SessionService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new EmailService(Database::getInstance(), Mailer::getInstance()))->confirmCode(parent::$inputData["data"]["emailCode"], parent::$inputData["data"]["hash"], 1);

        (new Response())->setResponse(["success" => (new UserService(Database::getInstance()))->resetPassword(
            parent::$inputData["data"]["newPassword"],
            parent::$inputData["headers"]["HTTP_SESSION_ID"]
        )])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws ORMException
     * @throws InvalidParameter
     */
    public function changeName(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "newName" => "required",
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        if (preg_match("/^a?id\d+/", parent::$inputData["data"]["newName"]))
            throw new InvalidParameter("newName shouldn't contains (a)id prefix");

        (new TokenService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["changed" =>
            (new UserService(Database::getInstance()))->changeName(parent::$inputData["data"]["newName"], parent::$inputData["headers"]["HTTP_TOKEN"])
        ])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws ORMException
     */
    public function get(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "aId" => "required|numeric",
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new TokenService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(
            (new UserService(Database::getInstance()))->get((int) parent::$inputData["data"]["aId"], parent::$inputData["headers"]["HTTP_TOKEN"])
        )->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws ORMException
     */
    public function search(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "query" => "required",
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new TokenService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(
            (new UserService(Database::getInstance()))->search(parent::$inputData["data"]["query"])
        )->send();
    }
}