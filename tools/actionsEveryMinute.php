<?php

require_once "vendor/autoload.php";

use Eviger\Database;

if ($argv[1] == "resetSessionAuth") {

    $data = Database::getInstance()->query("SELECT * FROM eviger_attempts_auth");
    
    while ($data_fetched = $data->fetchAssoc()) {
        
        if (time() > $data_fetched["time"] + 3600) {

            Database::getInstance()->query("DELETE FROM eviger_attempts_auth WHERE id = ?i", $data_fetched["id"]);
            
        }
        
    }
    
} else {
    
    //TODO: anything
    
}
