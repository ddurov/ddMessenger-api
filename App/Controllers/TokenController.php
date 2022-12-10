<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Singletones\Database;
use Core\Controllers\Controller;
use Core\DTO\Response;
use Core\Exceptions\EntityNotFound;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Rakit\Validation\Validator;

class TokenController extends Controller
{
    private TokenService $tokenService;
    private SessionService $sessionService;

    /**
     * @throws ORMException
     * @throws Exception
     */
    public function __construct()
    {
        $this->tokenService = new TokenService(Database::getInstance());
        $this->sessionService = new SessionService(Database::getInstance());
        parent::__construct();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws ORMException
     */
    public function create(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "tokenType" => "required|numeric",
            "HTTP_SESSION_ID" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new Response())->setResponse(["token" =>
            $this->tokenService->create(
                (int) parent::$inputData["data"]["tokenType"],
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        ])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     */
    public function get(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "tokenType" => "required|numeric",
            "HTTP_SESSION_ID" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new Response())->setResponse(["token" =>
            $this->tokenService->get(
                (int) parent::$inputData["data"]["tokenType"],
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        ])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     */
    public function check(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required",
            "HTTP_SESSION_ID" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new Response())->setResponse(["valid" =>
            $this->tokenService->check(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        ])->send();
    }
}