<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Singleton\Database;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityException;
use Core\Exceptions\ParametersException;
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
	 * @throws ORMException|ParametersException|EntityException
	 */
	public function create(): void
	{
		parent::validateData(parent::$inputData["headers"], [
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->sessionService->create(
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws ORMException|ParametersException|EntityException
	 */
	public function get(): void
	{
		parent::validateData(parent::$inputData["headers"], [
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->sessionService->get(
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws ParametersException|EntityException
	 */
	public function check(): void
	{
		parent::validateData(parent::$inputData["headers"], [
			"HTTP_TOKEN" => "required",
			"HTTP_SESSION_ID" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->sessionService->check(
				parent::$inputData["headers"]["HTTP_SESSION_ID"]
			)
		)->send();
	}
}