<?php

require_once "vendor/autoload.php";

use Krugozor\Database\Mysql;

$db = Mysql::create("localhost", "root", "DDurov1ater!")->setDatabaseName("eviger")->setCharset("utf8mb4");

if ($argv[1] == "resetSessionAuth") {

    $data = $db->query("SELECT * FROM eviger_attempts_auth");
    
    while ($data_fetched = $data->fetchAssoc()) {
        
        if (time() > $data_fetched["time"] + 3600) {
            
            $db->query("DELETE FROM eviger_attempts_auth WHERE id = ?i", $data_fetched["id"]);
            
        }
        
    }
    
} else {
    
    //TODO: anything
    
}