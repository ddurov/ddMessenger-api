<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\MessageService;
use Api\Services\TokenService;
use Api\Singletones\Database;
use Core\Controllers\Controller;
use Core\DTO\Response;
use Core\Exceptions\EntityNotFound;
use Core\Exceptions\InvalidParameter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;

class MessageController extends Controller
{
    private MessageService $messageService;
    private TokenService $tokenService;

    /**
     * @throws ORMException
     * @throws Exception
     */
    public function __construct()
    {
        $this->messageService = new MessageService(Database::getInstance());
        $this->tokenService = new TokenService(Database::getInstance());
        parent::__construct();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws InvalidParameter
     * @throws ORMException
     */
    public function send(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "aId" => "required|numeric",
            "text" => "required",
            "HTTP_TOKEN" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["id" =>
            $this->messageService->send(
                (int) parent::$inputData["data"]["aId"],
                parent::$inputData["data"]["text"],
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        ])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws InvalidParameter
     * @throws ORMException
     */
    public function getHistory(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "aId" => "required|numeric",
            "HTTP_TOKEN" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(
            $this->messageService->getHistory(
                (int) parent::$inputData["data"]["aId"],
                parent::$inputData["data"]["offset"] ?? null,
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        )->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws InvalidParameter
     */
    public function getDialogs(): void
    {
        parent::validateData(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(
            $this->messageService->getDialogs(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        )->send();
    }
}