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
    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws ORMException
     */
    public function create(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new TokenService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["sessionId" =>
            (new SessionService(Database::getInstance()))->create(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
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
        $validation = (new Validator())->validate(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new TokenService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["sessionId" =>
            (new SessionService(Database::getInstance()))->get(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        ])->send();
    }

    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
     * @throws ORMException
     */
    public function check(): void
    {
        $validation = (new Validator())->validate(parent::$inputData["headers"], [
            "HTTP_TOKEN" => "required",
            "HTTP_SESSION_ID" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new TokenService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

        (new Response())->setResponse(["valid" =>
            (new SessionService(Database::getInstance()))->check(
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
            )
        ])->send();
    }
}