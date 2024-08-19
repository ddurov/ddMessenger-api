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
use Doctrine\ORM\OptimisticLockException;

class SessionController extends Controller
{
	private SessionService $sessionService;
	private TokenService $tokenService;

	/**
	 * @throws NotSupported
	 * @throws InternalError
	 */
	public function __construct()
	{
		$this->sessionService = new SessionService(Database::getEntityManager());
		$this->tokenService = new TokenService(Database::getEntityManager());
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ORMException
	 * @throws ParametersException
	 * @throws OptimisticLockException
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
	 * @throws EntityException
	 * @throws NotSupported
	 * @throws ParametersException
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
	 * @throws EntityException
	 * @throws ParametersException
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