<?php

namespace Eviger\Api\DTO;

use Exception;

class selfThrows extends Exception
{
    public function __construct($message, $code = 0) {
        parent::__construct(json_encode(["status" => "error", "response" => $message]), $code);
    }

}