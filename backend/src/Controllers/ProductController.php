<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class ProductController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listProducts(): void
    {
        $stmt = $this->pdo->query('SELECT * FROM products');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    }

    public function addProduct(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare('INSERT INTO products (name, type, unit, stock, cost) VALUES (:name, :type, :unit, :stock, :cost)');
        $stmt->execute([
            ':name' => $input['name'],
            ':type' => $input['type'],
            ':unit' => $input['unit'],
            ':stock' => $input['stock'],
            ':cost' => $input['cost'],
        ]);
        echo json_encode(['success' => true]);
    }

    public function editProduct(int $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $this->pdo->prepare('UPDATE products SET name = :name, type = :type, unit = :unit, stock = :stock, cost = :cost WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':name' => $input['name'],
            ':type' => $input['type'],
            ':unit' => $input['unit'],
            ':stock' => $input['stock'],
            ':cost' => $input['cost'],
        ]);
        echo json_encode(['success' => true]);
    }

    public function deleteProduct(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true]);
    }
}