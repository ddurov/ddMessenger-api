<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once "../../vendor/autoload.php";

use Eviger\Api\DTO\Response;
use Eviger\Api\DTO\selfThrows;
use Eviger\Api\Methods\Users;
use Eviger\Api\Tools\Other;
use Eviger\Database;

preg_match("~/longpoll/(.*)~", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches);

$method = $matches[1];

try {

    if (!isset($method)) throw new selfThrows(["message" => "method parameter is missing"]);

    if (!isset($_GET['token'])) throw new selfThrows(["message" => "token parameter is missing"]);

    switch ($method) {

        case "getUpdates":

            if (!isset($_GET['waitTime'])) throw new selfThrows(["message" => "waitTime parameter is missing"]);
            $nowSecond = 0;
            $dataCollected = [];

            while ($nowSecond < $_GET['waitTime']) {

                $longPollData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE eid = ?i", Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid']);

                while ($dataParsed = $longPollData->fetchAssoc()) {

                    if ((int)$dataParsed['isChecked'] === 0) {

                        if (isset($_GET['flags'])) {

                            $tempData = unserialize($dataParsed['dataSerialized']);
                            $dataToAdd = [];
                            $explodedFlags = explode(",", $_GET['flags']);

                            for ($numberExplode = 0; $numberExplode < count($explodedFlags); $numberExplode++) {

                                switch ($explodedFlags[$numberExplode]) {

                                    case "info_peerId":
                                        $dataToAdd[] = json_decode(Users::get($_GET['token'], "".$tempData['objects']['peer_id']), true)['response'];
                                        break;

                                }

                            }

                            $tempData['objects']['message'] = Other::decryptMessage($tempData['objects']['message']);
                            $tempData['objects']["flagsData"] = $dataToAdd;
                            $dataCollected[] = $tempData;

                        } else {

                            $dataCollected[] = unserialize($dataParsed['dataSerialized']);

                        }
                        Database::getInstance()->query("UPDATE eviger.eviger_longpoll_data SET isChecked = 1 WHERE id = ?i", $dataParsed['id']);

                    }

                }

                if (count($dataCollected) !== 0) break;

                $nowSecond++;
                sleep(1);

            }

            (new Response())->setStatus("ok")->setResponse($dataCollected)->send();
            break;

        default:
            throw new selfThrows(["message" => "unknown method", "parameters" => $_GET]);

    }

} catch (selfThrows $e) {

    die($e->getMessage());

} catch (Throwable $exceptions) {

    Other::log("Error: " . $exceptions->getMessage() . " on line: " . $exceptions->getLine() . " in: " . $exceptions->getFile());
    (new Response)->setStatus("error")->setResponse(["message" => "internal error, try later"])->send();

}