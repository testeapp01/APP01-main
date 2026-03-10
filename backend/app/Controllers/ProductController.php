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

        $sql = "SELECT id, nome, tipo, unidade, custo_medio FROM produtos {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset";
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
        $nome = isset($data['nome']) ? trim((string)$data['nome']) : '';
        if ($nome === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Nome obrigatório']);
            return;
        }

        $custoMedio = isset($data['custo_medio']) ? (float)$data['custo_medio'] : 0;
        if ($custoMedio < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'custo_medio não pode ser negativo']);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO produtos (nome, tipo, unidade, custo_medio) VALUES (:nome, :tipo, :unidade, :custo_medio)');
        try {
            $stmt->execute([
                'nome' => $nome,
                'tipo' => $data['tipo'] ?? null,
                'unidade' => $data['unidade'] ?? 'saco',
                'custo_medio' => $custoMedio,
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar produto.']);
            return;
        }

        http_response_code(201);
        echo json_encode(['id' => (int)$this->pdo->lastInsertId()]);
    }

    public function update(int $id): void
    {
        $existsStmt = $this->pdo->prepare('SELECT id FROM produtos WHERE id = :id LIMIT 1');
        $existsStmt->execute(['id' => $id]);
        if (!$existsStmt->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            return;
        }

        $data = \App\Helpers\Request::body();
        $nome = isset($data['nome']) ? trim((string)$data['nome']) : '';
        if ($nome === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Nome obrigatório']);
            return;
        }

        $custoMedio = isset($data['custo_medio']) ? (float)$data['custo_medio'] : 0;
        if ($custoMedio < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'custo_medio não pode ser negativo']);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE produtos SET nome = :nome, tipo = :tipo, unidade = :unidade, custo_medio = :custo_medio WHERE id = :id'
        );

        try {
            $stmt->execute([
                'id' => $id,
                'nome' => $nome,
                'tipo' => $data['tipo'] ?? null,
                'unidade' => $data['unidade'] ?? 'saco',
                'custo_medio' => $custoMedio,
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao atualizar produto.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() < 1) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            return;
        }

        http_response_code(204);
    }
}
