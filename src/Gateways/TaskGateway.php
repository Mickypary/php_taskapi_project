<?php

namespace App\Gateways;

use App\Controllers\Database;
use InvalidArgumentException;
use PDO;
use PDOStatement;

class TaskGateway
{
    private PDO $conn;
    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAllForUser(int $user_id): array
    {
        $sql = "SELECT * FROM task WHERE user_id = :user_id ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_completed'] = (bool) $row['is_completed'];

            $data[] = $row;
        }

        return $data;
    }

    public function getForUser(int $user_id, int $id): array|false
    {
        $sql = "SELECT * FROM task WHERE id = :id AND user_id = :user_id ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $data =  $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data !== false) {
            $data['is_completed'] = (bool) $data['is_completed'];
        }

        return $data;
    }

    public function createForUser(int $user_id, array $data): string
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Data cannot be empty");
        }

        // Add user_id to the data array
        $data['user_id'] = $user_id;

        $array_key = array_keys($data);
        $columns  = implode(",", $array_key);
        $placeholders  = implode(",:", $array_key);
        $sql = "INSERT INTO task ({$columns}) VALUES (:{$placeholders})";
        $stmt = $this->conn->prepare($sql);
        foreach ($data as $key => $value) {
            $type =  match (gettype($value)) {
                'integer'  => PDO::PARAM_INT,
                'boolean'  => PDO::PARAM_BOOL,
                'NULL'  => PDO::PARAM_NULL,
                default  => PDO::PARAM_STR,
            };

            $stmt->bindValue(":{$key}", $value, $type);
        }

        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function updateForUser(int $user_id, string $id, array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Data cannot be empty");
        }

        $array_key = array_keys($data);
        $columns  = implode(",", array_map(
            fn($key) =>
            "{$key} = :{$key}",
            $array_key
        ));
        // print_r($array_key);

        $sql = "UPDATE task SET {$columns} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        foreach ($data as $key => $value) {
            $type =  match (gettype($value)) {
                'integer'  => PDO::PARAM_INT,
                'boolean'  => PDO::PARAM_BOOL,
                'NULL'  => PDO::PARAM_NULL,
                default  => PDO::PARAM_STR,
            };

            $stmt->bindValue(":{$key}", $value, $type);
        }

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function deleteForUser(int $user_id, string $id): int
    {
        $sql = "DELETE FROM task WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
