<?php

namespace Api\LongPoll\Controllers;

use Api\LongPoll\Service\EventService;
use Api\Services\TokenService;
use Api\Singletone\Database;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityException;
use Core\Exceptions\ParametersException;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class EventController extends Controller
{
    private EventService $eventService;
    private TokenService $tokenService;

    /**
     * @throws Exception
     * @throws MissingMappingDriverImplementation
     * @throws NotSupported
     */
    public function __construct()
    {
        $this->eventService = new EventService(Database::getInstance());
        $this->tokenService = new TokenService(Database::getInstance());
        parent::__construct();
    }

    /**
     * @return void
     * @throws ParametersException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EntityException
     */
    public function listen(): void
    {
        parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
            "timeout" => "required|numeric",
            "HTTP_TOKEN" => "required"
        ]);

        $this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new SuccessResponse())->setBody(
            $this->eventService->listen(
                parent::$inputData["data"]["timeout"],
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        )->send();
    }
}