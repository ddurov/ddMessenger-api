<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\SessionService;
use Api\Services\TokenService;
use Api\Singleton\Database;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityException;
use Core\Exceptions\InternalError;
use Core\Exceptions\ParametersException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;

class TokenController extends Controller
{
	private TokenService $tokenService;
	private SessionService $sessionService;

	/**
	 * @throws NotSupported
	 * @throws InternalError
	 */
	public function __construct()
	{
		$this->tokenService = new TokenService(Database::getEntityManager());
		$this->sessionService = new SessionService(Database::getEntityManager());
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ORMException
	 * @throws ParametersException
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
				parent::$inputData["data"]["tokenType"],
				parent::$inputData["headers"]["HTTP_SESSION_ID"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws NotSupported
	 * @throws ParametersException
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
				(int)parent::$inputData["data"]["tokenType"],
				parent::$inputData["headers"]["HTTP_SESSION_ID"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ParametersException
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