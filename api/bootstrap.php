<?php

require dirname(__DIR__) . "/vendor/autoload.php";

// Exception & Error Handler
set_error_handler([App\Controllers\ErrorHandler::class, 'handleError']);
set_exception_handler("App\Controllers\ErrorHandler::handleException"); // works

// DOTENV
$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();


header('Content-Type:application/json; charset=UTF-8');
