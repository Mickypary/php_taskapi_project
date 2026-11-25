<?php

declare(strict_types=1);

use App\Controllers\Database;
use App\Gateways\UserGateway;
use App\JWTCodec;
use App\RefreshTokenGateway;

require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$data = (array) json_decode(file_get_contents("php://input"), true);

if (!array_key_exists('username', $data) || !array_key_exists('password', $data)) {
    http_response_code(400);
    echo json_encode(['message' => 'missing login credentials']);
    exit;
}

$database = new Database($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'], (int) $_ENV['DB_PORT']);

$user_gateway = new UserGateway($database);
$user = $user_gateway->getByUsername($data['username']);

if ($user === false) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid authentication']);
    exit;
}

if (!password_verify($data['password'], $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid authentication']);
    exit;
}

// Using JWT
$codec = new JWTCodec($_ENV['SECRET_KEY']);

// using base64_encode
// $access_token = base64_encode(json_encode($payload));

require __DIR__ . '/tokens.php';

$refresh_token_gateway = new RefreshTokenGateway($database, $_ENV['SECRET_KEY']);

$refresh_token_gateway->create($refresh_token, $refresh_token_expiry);
