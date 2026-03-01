<?php
namespace App\Controllers;

use App\Repositories\ClientRepository;
use PDO;

class ClientController
{
    public function __construct(private PDO $pdo)
    {
    }


    public function index(): void
    {
        $repo = new ClientRepository($this->pdo);
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
        // Normalize status, uf, and ensure all fields are present
        $data['endereco'] = $data['endereco'] ?? null;
        $data['numero'] = $data['numero'] ?? null;
        $data['complemento'] = $data['complemento'] ?? null;
        $data['bairro'] = $data['bairro'] ?? null;
        $data['cep'] = $data['cep'] ?? null;
        $data['cpf_cnpj'] = $data['cpf_cnpj'] ?? null;
        $data['telefone'] = $data['telefone'] ?? null;
        $data['email'] = $data['email'] ?? null;
        $data['uf'] = $data['uf'] ?? null;
        $data['cidade'] = $data['cidade'] ?? null;
        require_once __DIR__.'/../Helpers/Validator.php';
        if ($data['cpf_cnpj']) {
            if (strlen($data['cpf_cnpj']) == 11 && !Validator::validateCPF($data['cpf_cnpj'])) {
                http_response_code(400);
                echo json_encode(['error' => 'CPF inválido']);
                return;
            }
            if (strlen($data['cpf_cnpj']) == 14 && !Validator::validateCNPJ($data['cpf_cnpj'])) {
                http_response_code(400);
                echo json_encode(['error' => 'CNPJ inválido']);
                return;
            }
        }
        if ($data['telefone'] && !Validator::validateTelefone($data['telefone'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Telefone inválido']);
            return;
        }
        if ($data['email'] && !Validator::validateEmail($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido']);
            return;
        }
        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? 1 : 0;
        } else {
            $data['status'] = 1;
        }
        $repo = new ClientRepository($this->pdo);
        $id = $repo->create($data);
        http_response_code(201);
        echo json_encode(['id' => $id]);
    }

    public function delete(int $id): void
    {
        try {
            $repo = new ClientRepository($this->pdo);
            $repo->delete($id);
            echo json_encode(['message' => 'Cliente removido com sucesso']);
        } catch (\PDOException $e) {
            http_response_code(409);
            echo json_encode(['error' => 'Não foi possível excluir o cliente. Verifique vínculos com vendas.']);
        }
    }
}
