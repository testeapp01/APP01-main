<?php
namespace App\Controllers;

use App\Repositories\MotoristaRepository;
use PDO;

class MotoristaController
{
    public function __construct(private PDO $pdo)
    {
    }


    public function index(): void
    {
        $repo = new MotoristaRepository($this->pdo);
        $items = $repo->all();
        echo json_encode($items);
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome obrigatório']);
            return;
        }
        // Normalize and ensure all fields are present
        $data['cpf'] = $data['cpf'] ?? null;
        $data['placa'] = $data['placa'] ?? null;
        $data['veiculo'] = $data['veiculo'] ?? null;
        $data['uf'] = $data['uf'] ?? null;
        $data['telefone'] = $data['telefone'] ?? null;
        $data['TpCaminhao'] = $data['TpCaminhao'] ?? null;
        require_once __DIR__.'/../Helpers/Validator.php';
        if ($data['cpf'] && !Validator::validateCPF($data['cpf'])) {
            http_response_code(400);
            echo json_encode(['error' => 'CPF inválido']);
            return;
        }
        if ($data['telefone'] && !Validator::validateTelefone($data['telefone'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Telefone inválido']);
            return;
        }
        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? 1 : 0;
        } else {
            $data['status'] = 1;
        }
        $repo = new MotoristaRepository($this->pdo);
        $id = $repo->create($data);
        http_response_code(201);
        echo json_encode(['id' => $id]);
    }

    public function update(int $id): void
    {
        $data = \App\Helpers\Request::body();
        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome obrigatório']);
            return;
        }

        $data['cpf'] = $data['cpf'] ?? null;
        $data['placa'] = $data['placa'] ?? null;
        $data['veiculo'] = $data['veiculo'] ?? null;
        $data['uf'] = $data['uf'] ?? null;
        $data['telefone'] = $data['telefone'] ?? null;
        $data['TpCaminhao'] = $data['TpCaminhao'] ?? null;

        require_once __DIR__.'/../Helpers/Validator.php';
        if ($data['cpf'] && !Validator::validateCPF($data['cpf'])) {
            http_response_code(400);
            echo json_encode(['error' => 'CPF inválido']);
            return;
        }
        if ($data['telefone'] && !Validator::validateTelefone($data['telefone'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Telefone inválido']);
            return;
        }

        $data['status'] = isset($data['status']) ? ($data['status'] ? 1 : 0) : 1;

        $repo = new MotoristaRepository($this->pdo);
        $found = $repo->findById($id);
        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Motorista não encontrado']);
            return;
        }

        $repo->update($id, $data);
        echo json_encode(['id' => $id, 'message' => 'Motorista atualizado com sucesso']);
    }

    public function listTiposCaminhao(): void
    {
        $stmt = $this->pdo->query('SELECT id, nome FROM tipos_caminhao ORDER BY nome');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($items ?: []);
    }

    public function delete(int $id): void
    {
        try {
            $repo = new MotoristaRepository($this->pdo);
            $repo->delete($id);
            echo json_encode(['message' => 'Motorista removido com sucesso']);
        } catch (\PDOException $e) {
            http_response_code(409);
            echo json_encode(['error' => 'Não foi possível excluir o motorista. Verifique vínculos com compras.']);
        }
    }
}
