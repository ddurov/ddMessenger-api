<?php declare(strict_types=1);

require_once "../../vendor/autoload.php";

use Core\DTO\Response;
use Core\Tools\Other;
use Core\Tools\selfThrows;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {

    /*$method = $matches[1];

    if (!isset($method) || $method === "") throw new selfThrows(["message" => "method parameter is missing or null"]);

    if (!isset($_GET['token'])) throw new selfThrows(["message" => "token parameter is missing"]);

    switch ($method) {

        case "getUpdates":

            if (!isset($_GET['waitTime'])) throw new selfThrows(["message" => "waitTime parameter is missing"]);
            $nowSecond = 0;
            $finalData = [];

            while ($nowSecond < $_GET['waitTime']) {

                $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'];
                $databaseData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE instr(eviger.eviger_longpoll_data.peers, '?i') > 0", $myId);

                while ($databaseDataPrepared = $databaseData->fetchAssoc()) {

                    $whoCheckedEvent = unserialize($databaseDataPrepared['whoChecked']);

                    if (!in_array($myId, $whoCheckedEvent)) {

                        $data = unserialize($databaseDataPrepared['dataSerialized']);

                        if (isset($_GET['flags'])) {

                            $flagsData = [];
                            $explodedFlags = explode(",", $_GET['flags']);
                            $explodedPeers = explode(",", $databaseDataPrepared['peers']);

                            $data['objects']['peerId'] = (int) $explodedPeers[0] === $myId ? (int) $explodedPeers[1] : (int) $explodedPeers[0];

                            for ($numberExplode = 0; $numberExplode < count($explodedFlags); $numberExplode++) {

                                switch ($explodedFlags[$numberExplode]) {

                                    case "peerIdInfo":
                                        $flagsData[] = json_decode(Users::get($data['objects']['peerId'], $_GET['token']), true)['response'];
                                        break;

                                }

                            }

                            $data['objects']['message'] = Other::decryptMessage($data['objects']['message']);
                            $data['objects']['flagsData'] = $flagsData;

                        }
                        $finalData[] = $data;
                        $whoCheckedEvent[] = $myId;
                        Database::getInstance()->query("UPDATE eviger.eviger_longpoll_data SET whoChecked = '?s' WHERE id = ?i", serialize($whoCheckedEvent), $databaseDataPrepared['id']);

                    }

                }

                if (count($finalData) !== 0) break;

                $nowSecond++;
                sleep(1);

            }

            (new Response())->setStatus("ok")->setResponse($finalData)->send();
            break;

        default:
            throw new selfThrows(["message" => "unknown method", "parameters" => $_GET]);

    }*/

} catch (selfThrows $e) {

    die($e->getMessage());

} catch (Throwable $exceptions) {

    Other::log("Error: " . $exceptions->getMessage() . " on line: " . $exceptions->getLine() . " in: " . $exceptions->getFile());
    (new Response)->setStatus("error")->setCode(500)->setResponse(["message" => "internal error, try later"])->send();

}
