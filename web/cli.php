<?php

if (php_sapi_name() !== 'cli-server') {
    die('this is only for the php development server');
}

if (is_file(__DIR__.$_SERVER['SCRIPT_NAME'])) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

require 'index.php';