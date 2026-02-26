<?php
namespace App\Controllers;

use PDO;

class ProductController
{
    public function __construct(private PDO $pdo)
    {
    }


    public function index(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        $offset = ($page - 1) * $per;

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = 'WHERE nome LIKE :q';
            $params[':q'] = "%{$q}%";
        }

        $countSql = "SELECT COUNT(*) as total FROM produtos {$where}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT id, nome, tipo, unidade, estoque_atual, custo_medio FROM produtos {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['items' => $items, 'total' => $total]);
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome obrigatório']);
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO produtos (nome, tipo, unidade, estoque_atual, custo_medio) VALUES (:nome, :tipo, :unidade, :estoque_atual, :custo_medio)');
        $stmt->execute([
            'nome' => $data['nome'],
            'tipo' => $data['tipo'] ?? null,
            'unidade' => $data['unidade'] ?? 'saco',
            'estoque_atual' => $data['estoque_atual'] ?? 0,
            'custo_medio' => $data['custo_medio'] ?? 0,
        ]);
        http_response_code(201);
        echo json_encode(['id' => (int)$this->pdo->lastInsertId()]);
    }

    // Add update and delete stubs for full CRUD
    public function update(int $id): void
    {
        // TODO: Implement update logic
        http_response_code(501);
        echo json_encode(['error' => 'Not implemented']);
    }

    public function delete(int $id): void
    {
        // TODO: Implement delete logic
        http_response_code(501);
        echo json_encode(['error' => 'Not implemented']);
    }
}
