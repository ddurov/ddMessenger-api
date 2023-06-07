<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\EmailService;
use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Services\UserService;
use Api\Singletone\Database;
use Api\Singletone\Mailer;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityException;
use Core\Exceptions\ParametersException;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;

class UserController extends Controller
{
    private UserService $userService;
    private EmailService $emailService;
    private SessionService $sessionService;
    private TokenService $tokenService;

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws ORMException
     * @throws Exception
     */
    public function __construct()
    {
        $this->userService = new UserService(Database::getInstance());
        $this->emailService = new EmailService(Database::getInstance(), Mailer::getInstance());
        $this->sessionService = new SessionService(Database::getInstance());
        $this->tokenService = new TokenService(Database::getInstance());
        parent::__construct();
    }

    /**
     * @return void
     * @throws ORMException|ParametersException|EntityException
     */
    public function register(): void
    {
        parent::validateData(parent::$inputData["data"], [
            "login" => "required|between:6,64",
            "password" => "required|min:8",
            "username" => "required|min:4",
            "email" => "required|regex:/(.*)@([\w\-\.]+)\.([\w]+)/",
            "emailCode" => "required",
            "hash" => "required"
        ]);

        if (preg_match("/\W/", parent::$inputData["data"]["login"]))
            throw new ParametersException("login has incorrect symbols");

        if (preg_match("/\W/", parent::$inputData["data"]["password"]))
            throw new ParametersException("password has incorrect symbols");

        if (preg_match("/^a?id\d+/", parent::$inputData["data"]["username"]))
            throw new ParametersException("username shouldn't contains (a)id prefix");

        $this->emailService->confirmCode(
            parent::$inputData["data"]["emailCode"], parent::$inputData["data"]["hash"], 1
        );

        (new SuccessResponse())->setBody(
            $this->userService->register(
                parent::$inputData["data"]["login"],
                parent::$inputData["data"]["password"],
                parent::$inputData["data"]["username"],
                parent::$inputData["data"]["email"]
            )
        )->send();
    }

    /**
     * @return void
     * @throws Exception
     * @throws ORMException|ParametersException|EntityException
     */
    public function auth(): void
    {
        parent::validateData(parent::$inputData["data"], [
            "login" => "required|between:6,64",
            "password" => "required|min:8"
        ]);

        if (preg_match("/\W/", parent::$inputData["data"]["login"]))
            throw new ParametersException("login has incorrect symbols");

        if (preg_match("/\W/", parent::$inputData["data"]["password"]))
            throw new ParametersException("password has incorrect symbols");

        (new SuccessResponse())->setBody(
            $this->userService->auth(
                parent::$inputData["data"]["login"],
                parent::$inputData["data"]["password"]
            )
        )->send();
    }

    /**
     * @return void
     * @throws ORMException|ParametersException|EntityException
     */
    public function resetPassword(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "newPassword" => "required|min:8",
            "emailCode" => "required",
            "hash" => "required",
            "HTTP_SESSION_ID" => "required"
        ]);

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        $this->emailService->confirmCode(
            parent::$inputData["data"]["emailCode"],
            parent::$inputData["data"]["hash"], 1
        );

        $this->userService->resetPassword(
            parent::$inputData["data"]["newPassword"],
            parent::$inputData["headers"]["HTTP_SESSION_ID"]
        );

        (new SuccessResponse())->setBody(true)->send();
    }

    /**
     * @return void
     * @throws ParametersException|EntityException|ORMException
     */
    public function changeName(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "newName" => "required",
            "HTTP_TOKEN" => "required"
        ]);

        if (preg_match("/^a?id\d+/", parent::$inputData["data"]["newName"]))
            throw new ParametersException("newName shouldn't contains (a)id prefix");

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        $this->userService->changeName(
            parent::$inputData["data"]["newName"],
            parent::$inputData["headers"]["HTTP_TOKEN"]
        );

        (new SuccessResponse())->setBody(true)->send();
    }

    /**
     * @return void
     * @throws ORMException|ParametersException|EntityException
     */
    public function get(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "aId" => "required|numeric",
            "HTTP_TOKEN" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new SuccessResponse())->setBody(
            $this->userService->get(
                (int) parent::$inputData["data"]["aId"],
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        )->send();
    }

    /**
     * @return void
     * @throws ParametersException|EntityException
     */
    public function search(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "query" => "required",
            "HTTP_TOKEN" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new SuccessResponse())->setBody(
            $this->userService->search(
                parent::$inputData["data"]["query"]
            )
        )->send();
    }
}