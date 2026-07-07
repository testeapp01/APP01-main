<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Request;

class TabelaPrecoController
{
    public function __construct(private PDO $pdo)
    {
    }

    /** GET /api/v1/tabelas-preco */
    public function index(): void
    {
        $stmt = $this->pdo->query(
            'SELECT id, nome, tipo, desconto_percentual, ativa, created_at FROM tabelas_preco ORDER BY nome ASC'
        );
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** POST /api/v1/tabelas-preco */
    public function create(): void
    {
        $data = Request::body();
        $nome = trim((string)($data['nome'] ?? ''));

        if ($nome === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Nome é obrigatório']);
            return;
        }

        $tipo      = trim((string)($data['tipo'] ?? 'padrao'));
        $desconto  = (float)($data['desconto_percentual'] ?? 0);
        $ativa     = isset($data['ativa']) ? (int)(bool)$data['ativa'] : 1;

        $allowed = ['atacado','varejo','especial','padrao'];
        if (!in_array($tipo, $allowed, true)) $tipo = 'padrao';

        $stmt = $this->pdo->prepare(
            'INSERT INTO tabelas_preco (nome, tipo, desconto_percentual, ativa) VALUES (:nome, :tipo, :desc, :ativa)'
        );
        $stmt->execute(['nome' => $nome, 'tipo' => $tipo, 'desc' => $desconto, 'ativa' => $ativa]);

        http_response_code(201);
        echo json_encode(['id' => (int)$this->pdo->lastInsertId()]);
    }

    /** PUT /api/v1/tabelas-preco/{id} */
    public function update(int $id): void
    {
        $data  = Request::body();
        $sets  = [];
        $params = ['id' => $id];

        if (isset($data['nome']) && trim($data['nome']) !== '') {
            $sets[] = 'nome = :nome';
            $params['nome'] = trim($data['nome']);
        }
        if (isset($data['desconto_percentual'])) {
            $sets[] = 'desconto_percentual = :desc';
            $params['desc'] = (float)$data['desconto_percentual'];
        }
        if (isset($data['ativa'])) {
            $sets[] = 'ativa = :ativa';
            $params['ativa'] = (int)(bool)$data['ativa'];
        }

        if (empty($sets)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE tabelas_preco SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Tabela de preço não encontrada']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
