<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Singletones\Database;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityNotFound;
use Core\Exceptions\InvalidParameter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;

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
     * @throws InvalidParameter
     * @throws ORMException
     */
    public function create(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "tokenType" => "required|numeric",
            "HTTP_SESSION_ID" => "required"
        ]);

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new SuccessResponse())->setBody(
            $this->tokenService->create(
                (int) parent::$inputData["data"]["tokenType"],
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        )->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws InvalidParameter
     */
    public function get(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "tokenType" => "required|numeric",
            "HTTP_SESSION_ID" => "required"
        ]);

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new SuccessResponse())->setBody(
            $this->tokenService->get(
                (int) parent::$inputData["data"]["tokenType"],
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        )->send();
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

        $this->sessionService->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new SuccessResponse())->setBody(
            $this->tokenService->check(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        )->send();
    }
}