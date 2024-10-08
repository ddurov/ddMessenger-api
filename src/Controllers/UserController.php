<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Models\UserModel;
use Api\Services\EmailService;
use Api\Services\TokenService;
use Api\Services\UserService;
use Api\Singleton\Database;
use Api\Singleton\Mailer;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\EntityException;
use Core\Exceptions\InternalError;
use Core\Exceptions\ParametersException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use PHPMailer\PHPMailer\Exception;

class UserController extends Controller
{
	private UserService $userService;
	private EmailService $emailService;
	private TokenService $tokenService;

	/**
	 * @throws InternalError
	 * @throws NotSupported
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->userService = new UserService(Database::getEntityManager());
		$this->emailService = new EmailService(Database::getEntityManager(), Mailer::getInstance());
		$this->tokenService = new TokenService(Database::getEntityManager());
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ORMException
	 * @throws ParametersException
	 */
	public function register(): void
	{
		parent::validateData(parent::$inputData["data"], [
			"login" => "required|between:6,64",
			"password" => "required|min:8",
			"username" => "required|min:4",
			"email" => "required|regex:/(.*)@([\w\-\.]+)\.([\w]+)/",
			"emailCode" => "required",
			"hash" => "required"
		]);

		if (preg_match("#[^a-zA-Z0-9@$!%*?&+~|{}:;<>/.]+#", parent::$inputData["data"]["login"]))
			throw new ParametersException("login has incorrect symbols");

		if (preg_match("#[^a-zA-Z0-9@$!%*?&+~|{}:;<>/.]+#", parent::$inputData["data"]["password"]))
			throw new ParametersException("password has incorrect symbols");

		$this->emailService->confirmCode(
			parent::$inputData["data"]["emailCode"],
			parent::$inputData["data"]["hash"],
			1
		);

		(new SuccessResponse())->setBody(
			$this->userService->register(
				parent::$inputData["data"]["login"],
				parent::$inputData["data"]["password"],
				parent::$inputData["data"]["username"],
				parent::$inputData["data"]["email"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ParametersException
	 */
	public function auth(): void
	{
		parent::validateData(parent::$inputData["data"], [
			"login" => "required|between:6,64",
			"password" => "required|min:8"
		]);

		if (preg_match("#[^a-zA-Z0-9@$!%*?&+~|{}:;<>/.]+#", parent::$inputData["data"]["login"]))
			throw new ParametersException("login has incorrect symbols");

		if (preg_match("#[^a-zA-Z0-9@$!%*?&+~|{}:;<>/.]+#", parent::$inputData["data"]["password"]))
			throw new ParametersException("password has incorrect symbols");

		(new SuccessResponse())->setBody(
			$this->userService->auth(
				parent::$inputData["data"]["login"],
				parent::$inputData["data"]["password"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws InternalError
	 * @throws NotSupported
	 * @throws ORMException
	 * @throws ParametersException
	 * @throws Exception
	 */
	public function resetPassword(): void
	{
		parent::validateData(parent::$inputData["data"], [
			"login" => "required",
			"newPassword" => "required|min:8"
		]);

		if (!isset(parent::$inputData["data"]["emailCode"])) {
			$user = Database::getEntityManager()->getRepository(UserModel::class)
				->findOneBy(["login" => parent::$inputData["data"]["login"]]);

			if ($user === null)
				throw new EntityException("current entity 'account by login' not found", 404);

			(new SuccessResponse())->setBody(
				$this->emailService->createCode(
					Database::getEntityManager()->getRepository(UserModel::class)
						->findOneBy(["login" => parent::$inputData["data"]["login"]])
						->getEmail()
				)
			)->setCode(202)->send();
		}

		parent::validateData(parent::$inputData["data"], [
			"emailCode" => "required",
			"hash" => "required"
		]);

		$this->emailService->confirmCode(
			parent::$inputData["data"]["emailCode"],
			parent::$inputData["data"]["hash"],
			1
		);

		$this->userService->resetPassword(
			parent::$inputData["data"]["login"],
			parent::$inputData["data"]["newPassword"]
		);

		(new SuccessResponse())->setBody(true)->send();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ORMException
	 * @throws ParametersException
	 */
	public function changeName(): void
	{
		parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
			"newName" => "required",
			"HTTP_TOKEN" => "required"
		]);

		if (preg_match("/^a?id\d+/", parent::$inputData["data"]["newName"]))
			throw new ParametersException("newName shouldn't contains (a)id prefix");

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		$this->userService->changeName(
			parent::$inputData["data"]["newName"],
			parent::$inputData["headers"]["HTTP_TOKEN"]
		);

		(new SuccessResponse())->setBody(true)->send();
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
			"aId" => "numeric",
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->userService->get(
				parent::$inputData["data"]["aId"],
				parent::$inputData["headers"]["HTTP_TOKEN"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws EntityException
	 * @throws ParametersException
	 */
	public function search(): void
	{
		parent::validateData(parent::$inputData["data"] + parent::$inputData["headers"], [
			"query" => "required",
			"HTTP_TOKEN" => "required"
		]);

		$this->tokenService->check(parent::$inputData["headers"]["HTTP_TOKEN"]);

		(new SuccessResponse())->setBody(
			$this->userService->search(
				parent::$inputData["data"]["query"]
			)
		)->send();
	}
}