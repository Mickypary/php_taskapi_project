<?php

namespace App\Controllers;

use App\Gateways\TaskGateway;

class TaskController
{
    public function __construct(private TaskGateway $gateway, private int $user_id) {}

    public function processRequest(string $method, ?int $id): void
    {
        if ($id === null) {
            if ($method === 'GET') {
                echo json_encode($this->gateway->getAllForUser($this->user_id));
            } elseif ($method === 'POST') {
                $data = (array) json_decode(file_get_contents("php://input"), true); // file_get_contents("php://input") is use to read raw data directly from the request body. 
                $errors = $this->getValidationErrors($data);
                if (!empty($errors)) {
                    $this->respondUnprocessableEntity($errors);
                    return;
                }
                $id = $this->gateway->createForUser($this->user_id, $data);
                $this->responseCreated($id);
                // print_r($_POST);
            } else {
                $this->methodNotAllowed('GET,POST');
                echo json_encode([
                    'message' => 'Method Not Allowed'
                ]);
                exit;
            }
        } else {
            $task = $this->gateway->getForUser($this->user_id, $id);

            if ($task == false) {
                $this->notFound($id);
                return;
            }

            switch ($method) {
                case "GET":
                    echo json_encode($task);
                    break;

                case "PATCH":
                    $data = (array) json_decode(file_get_contents("php://input"), true);
                    $errors = $this->getValidationErrors($data, false);
                    if (!empty($errors)) {
                        $this->respondUnprocessableEntity($errors);
                        return;
                    }
                    $rows = $this->gateway->updateForUser($this->user_id, $id, $data);
                    echo json_encode(['message' => 'Task updated', 'rows' => $rows]);
                    break;

                case "DELETE":
                    $rows = $this->gateway->deleteForUser($this->user_id, $id);
                    echo json_encode(['message' => 'Task deleted', 'rows' => $rows]);
                    break;

                default:
                    $this->methodNotAllowed('GET,PATCH,DELETE');
            }
        }
    }

    private function methodNotAllowed(string $allowed_methods): void
    {
        http_response_code(405);
        // header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
        header("Allow: $allowed_methods");
    }

    private function notFound(string $id): void
    {
        http_response_code(404);
        echo json_encode([
            'message' => "Task with ID $id not found"
        ]);
    }

    private function responseCreated(string $id): void
    {
        http_response_code(201);
        echo json_encode([
            'id' => $id,
            'message' => "Task created"
        ]);
    }

    private function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode([
            'errors' => $errors
        ]);
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];

        if ($is_new && empty($data['name'])) {
            $errors[] = 'name is required';
        }

        if (!empty($data['priority'])) {
            if (filter_var($data['priority'], FILTER_VALIDATE_INT) === false) {
                $errors[] = "priority must be an integer";
            }
        }

        return $errors;
    }
}
