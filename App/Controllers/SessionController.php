<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Singletones\Database;
use Core\Controllers\Controller;
use Core\DTO\Response;
use Core\Exceptions\EntityNotFound;
use Core\Exceptions\InvalidParameter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;

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
     * @throws InvalidParameter
     */
    public function create(): void
    {
        parent::validateData(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

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
     * @throws InvalidParameter
     */
    public function get(): void
    {
        parent::validateData(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

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
     * @throws InvalidParameter
     */
    public function check(): void
    {
        parent::validateData(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required",
            "HTTP_SESSION_ID" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["valid" =>
            $this->sessionService->check(
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        ])->send();
    }
}