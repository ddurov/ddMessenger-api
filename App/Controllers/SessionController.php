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

class SessionController extends Controller
{
    private SessionService $sessionService;
    private TokenService $tokenService;

    /**
     * @throws ORMException
     * @throws Exception
     */
    public function __construct()
    {
        $this->sessionService = new SessionService(Database::getInstance());
        $this->tokenService = new TokenService(Database::getInstance());
        parent::__construct();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws ORMException
     */
    public function create(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["sessionId" =>
            $this->sessionService->create(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        ])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     */
    public function get(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["sessionId" =>
            $this->sessionService->get(
                parent::$inputData["headers"]["HTTP_TOKEN"]
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

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["valid" =>
            $this->sessionService->check(
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        ])->send();
    }
}