<?php declare(strict_types=1);

require_once "../vendor/autoload.php";

use Bramus\Router\Router;
use Core\DTO\Response;
use Core\Exceptions\CoreExceptions;
use Core\Exceptions\FunctionNotPassed;
use Core\Exceptions\RouteNotFound;
use Core\Tools\Other;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$router = new Router();
$router->setNamespace("\Api\Controllers");

try {

    $router->get("/", function () {
        echo "API for ddMessager";
    });

    $router->mount("/methods", function () use ($router) {

        $router->get("/", function () {
            throw new FunctionNotPassed("method not passed");
        });

        $router->mount("/email", function () use ($router) {

            $router->get("/", function () {
                throw new FunctionNotPassed("function not passed");
            });

            $router->post("/createCode", "EmailController@createCode");

            $router->get("/confirmCode", "EmailController@confirmCode");

        });

        $router->mount("/token", function () use ($router) {

            $router->get("/", function () {
                throw new FunctionNotPassed("method not passed");
            });

            $router->post("/create", "TokenController@create");

            $router->get("/get", "TokenController@get");

            $router->get("/check", "TokenController@check");

        });

        $router->mount("/session", function () use ($router) {

            $router->get("/", function () {
                throw new FunctionNotPassed("method not passed");
            });

            $router->post("/create", "SessionController@create");

            $router->get("/get", "SessionController@get");

            $router->get("/check", "SessionController@check");

        });

        $router->mount("/user", function () use ($router) {

            $router->get("/", function () {
                throw new FunctionNotPassed("method not passed");
            });

            $router->post("/register", "UserController@register");

            $router->get("/auth", "UserController@auth");

            $router->post("/resetPassword", "UserController@resetPassword");

            $router->post("/changeName", "UserController@changeName");

            $router->get("/get", "UserController@get");

            $router->get("/search", "UserController@search");

        });

        $router->mount("/messages", function () use ($router) {

            $router->get("/", function () {
                throw new FunctionNotPassed("method not passed");
            });

            $router->post("/send", "MessageController@send");

            $router->get("/getHistory", "MessageController@getHistory");

            $router->get("/getDialogs", "MessageController@getDialogs");

        });

    });

    $router->set404(function() {
        throw new RouteNotFound();
    });

    $router->run();

} catch (CoreExceptions $coreExceptions) {

    (new Response())->setStatus("error")->setCode($coreExceptions->getCode())->setResponse(["message" => $coreExceptions->getMessage()])->send();

} catch (Throwable $exceptions) {

    Other::log("Error: " . $exceptions->getMessage() . " on line: " . $exceptions->getLine() . " in: " . $exceptions->getFile());
    (new Response())->setStatus("error")->setCode(500)->setResponse(["message" => "internal error, try later"])->send();

}