<?php

//require_once "vendor/autoload.php";

// database already use these methods more effectivity

/*if ($argv[1] == "removeOldAttemptsOfAuth") {

    $data = Database::getInstance()->query("SELECT * FROM eviger_attempts_auth");

    while ($data_fetched = $data->fetchAssoc()) {

        if (time() > $data_fetched["time"] + 3600) Database::getInstance()->query("DELETE FROM eviger_attempts_auth WHERE id = ?i", $data_fetched["id"]);

    }

} elseif ($argv[1] == "removeOldRequestsOfEmail") {

    $data = Database::getInstance()->query("SELECT * FROM eviger_codes_email");

    while ($data_fetched = $data->fetchAssoc()) {

        if (time() > $data_fetched["date_request"] + 3600) Database::getInstance()->query("DELETE FROM eviger_codes_email WHERE id = ?i", $data_fetched["id"]);

    }

} else {

    //TODO: anything

}*/
