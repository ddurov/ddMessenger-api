<?php declare(strict_types=1);

require_once "vendor/autoload.php";

use Bramus\Router\Router;
use Core\DTO\ErrorResponse;
use Core\Exceptions\CoreExceptions;
use Core\Exceptions\ParametersException;
use Core\Exceptions\RouterException;
use Core\Tools\Other;

$router = new Router();
$router->setNamespace("\Api\LongPoll\Controllers");
$router->setBasePath("/methods/longpoll/");

try {

    $router->get("/", function () {
        throw new ParametersException("function not passed");
    });

    $router->get("/listen", "EventController@listen");

    $router->set404(function() {
        throw new RouterException("current route not found for this request method");
    });

    $router->run();

} catch (CoreExceptions $coreExceptions) {

    (new ErrorResponse())->setCode($coreExceptions->getCode())->setErrorMessage($coreExceptions->getMessage())->send();

} catch (Throwable $exceptions) {

    Other::log(
        "Error: " . $exceptions->getMessage() .
        " on line: " . $exceptions->getLine() .
        " in: " . $exceptions->getFile(),
        "messagerLongPoll"
    );
    (new ErrorResponse())->setErrorMessage("internal error, try later")->send();

}