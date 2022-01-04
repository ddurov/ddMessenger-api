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

                $myId = (int)Database::getInstance()->query("SELECT eid FROM eviger.eviger_tokens WHERE token = '?s'", $_GET['token'])->fetchAssoc()['eid'];
                $longPollData = Database::getInstance()->query("SELECT * FROM eviger.eviger_longpoll_data WHERE instr(eviger.eviger_longpoll_data.peers, '?i') > 0", $myId);

                while ($dataParsed = $longPollData->fetchAssoc()) {

                    $whoCheckedEvent = unserialize($dataParsed['whoChecked']);

                    if (!in_array($myId, $whoCheckedEvent)) {

                        $data = unserialize($dataParsed['dataSerialized']);

                        if (isset($_GET['flags'])) {

                            $flagsData = [];
                            $explodedFlags = explode(",", $_GET['flags']);
                            $explodedPeers = explode(",", $dataParsed['peers']);

                            $data['objects']['peerId'] = (int) $explodedPeers[0] === $myId ? (int) $explodedPeers[1] : (int) $explodedPeers[0];

                            for ($numberExplode = 0; $numberExplode < count($explodedFlags); $numberExplode++) {

                                switch ($explodedFlags[$numberExplode]) {

                                    case "peerIdInfo":
                                        $flagsData[] = json_decode(Users::get($_GET['token'], $data['objects']['peerId']), true)['response'];
                                        break;

                                }

                            }

                            $data['objects']['message'] = Other::decryptMessage($data['objects']['message']);
                            $data['objects']['flagsData'] = $flagsData;

                        }
                        $dataCollected[] = $data;
                        $whoCheckedEvent[] = $myId;
                        Database::getInstance()->query("UPDATE eviger.eviger_longpoll_data SET whoChecked = '?s' WHERE id = ?i", serialize($whoCheckedEvent), $dataParsed['id']);

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