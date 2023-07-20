<?php

namespace Api\LongPoll\Controllers;

use Core\Controllers\Controller;
use Core\DTO\SuccessResponse;

class EventController extends Controller
{
    public function listen(): void
    {
        (new SuccessResponse())->setBody("ok")->send(); // stub
    }
}