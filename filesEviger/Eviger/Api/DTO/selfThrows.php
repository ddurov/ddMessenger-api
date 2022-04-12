<?php

namespace Eviger\Api\DTO;

use Exception;

class selfThrows extends Exception
{
    public function __construct($message, $code = 500)
    {
        parent::__construct((new Response)->setCode($code)->setStatus("error")->setResponse($message)->toJson(), $code);
    }

}