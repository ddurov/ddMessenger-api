<?php declare(strict_types=1);

namespace Api\Controllers;

use Api\Services\EmailService;
use Api\Singleton\Database;
use Api\Singleton\Mailer;
use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;
use Core\Exceptions\InternalError;
use Core\Exceptions\ParametersException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use PHPMailer\PHPMailer\Exception;

class EmailController extends Controller
{
	private EmailService $emailService;

	/**
	 * @throws Exception
	 * @throws NotSupported
	 */
	public function __construct()
	{
		$this->emailService = new EmailService(Database::getEntityManager(), Mailer::getInstance());
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws InternalError
	 * @throws ORMException
	 * @throws Exception|ParametersException
	 */
	public function createCode(): void
	{
		parent::validateData(parent::$inputData["data"], [
			"email" => "required|email"
		]);

		if (
			preg_match(sprintf("/(.*)@%s/", getenv("ROOT_DOMAIN")), parent::$inputData["data"]["email"]) &&
			!json_decode(getenv("MAIL_ALLOW_SENDING_TO_LOCAL"))
		)
			throw new ParametersException(
				sprintf("email parameter shouldn't include \"%s\"", getenv("ROOT_DOMAIN"))
			);

		(new SuccessResponse())->setBody(
			$this->emailService->createCode(
				parent::$inputData["data"]["email"]
			)
		)->send();
	}

	/**
	 * @return void
	 * @throws ORMException|ParametersException
	 */
	public function confirmCode(): void
	{
		parent::validateData(parent::$inputData["data"], [
			"code" => "required",
			"hash" => "required",
			"needRemove" => "required|numeric"
		]);

		(new SuccessResponse())->setBody(
			$this->emailService->confirmCode(
				parent::$inputData["data"]["code"],
				parent::$inputData["data"]["hash"],
				parent::$inputData["data"]["needRemove"]
			)
		)->send();
	}
}