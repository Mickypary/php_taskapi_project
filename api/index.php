<?php

declare(strict_types=1);

// ini_set("display_errors", "On");

use App\Controllers\{Auth, Database, TaskController};
use App\Gateways\TaskGateway;
use App\Gateways\UserGateway;
use App\JWTCodec;
use Dotenv\Dotenv;

require __DIR__ . '/bootstrap.php';

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode('/', $path);


$resource = $parts[2];

$id = isset($parts[3]) ? (int) $parts[3] : null;

if ($resource !== 'tasks') {

    // header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found"); //HTTP response status line; HTTP-Version Status-Code Reason-Phrase
    // PHP's header() function is designed to send HTTP response header information, including the status line.
    http_response_code(404);
    echo json_encode([
        'message' => 'Not Found'
    ]);
    exit;
}


// DB Instance
$database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'], (int)$_ENV['DB_PORT']);

$user_gateway = new UserGateway($database);

$codec = new JWTCodec($_ENV['SECRET_KEY']);

$auth = new Auth($user_gateway, $codec);

// Using API KEY
// if (!$auth->authenticateAPIKey()) {
//     exit;
// }

// Using Access Token
// if (!$auth->authenticateAccessToken()) {
//     exit;
// }

// Using JWT
if (!$auth->authenticateJWT()) {
    exit;
}

// $user_id = $auth->getUserID();
$user_id = $auth->getUserID();

$task_gateway = new TaskGateway($database);

$controller = new TaskController($task_gateway, $user_id);

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);
