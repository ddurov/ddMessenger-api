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
    /**
     * @return void
     * @throws EntityNotFound
     * @throws Exception
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

        (new SessionService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new Response())->setResponse(["token" =>
            (new TokenService(Database::getInstance()))->create(
                (int) parent::$inputData["data"]["tokenType"],
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
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
        $validation = (new Validator())->validate(parent::$inputData["data"] + parent::$inputData["headers"], [
            "tokenType" => "required|numeric",
            "HTTP_SESSION_ID" => "required"
        ]);

        if (isset($validation->errors->all()[0]))
            (new Response())->setStatus("error")->setCode(400)->setResponse(["message" => $validation->errors->all()[0]])->send();

        (new SessionService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new Response())->setResponse(["token" =>
            (new TokenService(Database::getInstance()))->get(
                (int) parent::$inputData["data"]["tokenType"],
                parent::$inputData["headers"]["HTTP_SESSION_ID"]
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

        (new SessionService(Database::getInstance()))->check(parent::$inputData["headers"]["HTTP_SESSION_ID"]);

        (new Response())->setResponse(["valid" =>
            (new TokenService(Database::getInstance()))->check(
                parent::$inputData["headers"]["HTTP_TOKEN"]
            )
        ])->send();
    }
}