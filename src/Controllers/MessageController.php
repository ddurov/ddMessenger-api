<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\MessageService;
use Api\Services\TokenService;
use Api\Singleton\Database;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityException;
use Core\Exceptions\ParametersException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class MessageController extends Controller
{
	private MessageService $messageService;
	private TokenService $tokenService;

	/**
	 * @throws NotSupported
	 */
	public function __construct()
	{
		$this->messageService = new MessageService(Database::getEntityManager());
		$this->tokenService = new TokenService(Database::getEntityManager());
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws ORMException|ParametersException|EntityException
	 */
	public function send(): void
	{
		parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
			"aId" => "required|numeric",
			"text" => "required",
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->messageService->send(
				parent::$inputData["data"]["aId"],
				strval(parent::$inputData["data"]["text"]),
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws ORMException|ParametersException|EntityException
	 */
	public function getHistory(): void
	{
		parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
			"aId" => "required|numeric",
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->messageService->getHistory(
				parent::$inputData["data"]["aId"],
				parent::$inputData["data"]["offset"] ?? null,
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws ORMException|ParametersException|EntityException
	 */
	public function getDialogs(): void
	{
		parent::validateData(parent::$inputData["headers"], [
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->messageService->getDialogs(
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws ParametersException
	 * @throws NotSupported
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws EntityException
	 */
	public function getUpdates(): void
	{
		parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
			"timeout" => "required|numeric",
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->messageService->getUpdates(
				parent::$inputData["data"]["timeout"],
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}
}