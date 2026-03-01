<?php
namespace App\Controllers;

use PDO;

class FornecedorController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        $stmt = $this->pdo->query('SELECT id, razao_social, endereco, numero, complemento, bairro, cep, cidade, cnpj, email, telefone, inscricao_estadual, status, uf FROM fornecedores ORDER BY razao_social ASC');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($items);
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        if (empty($data['razao_social'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Razão social obrigatória']);
            return;
        }
        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? 1 : 0;
        } else {
            $data['status'] = 1;
        }
        $data['uf'] = $data['uf'] ?? null;
        $data['endereco'] = $data['endereco'] ?? null;
        $data['numero'] = $data['numero'] ?? null;
        $data['complemento'] = $data['complemento'] ?? null;
        $data['bairro'] = $data['bairro'] ?? null;
        $data['cep'] = $data['cep'] ?? null;
        $data['cidade'] = $data['cidade'] ?? null;
        require_once __DIR__.'/../Helpers/Validator.php';
        if ($data['cnpj'] && !Validator::validateCNPJ($data['cnpj'])) {
            http_response_code(400);
            echo json_encode(['error' => 'CNPJ inválido']);
            return;
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
        $stmt = $this->pdo->prepare('INSERT INTO fornecedores (razao_social, endereco, numero, complemento, bairro, cep, cidade, cnpj, email, telefone, inscricao_estadual, status, uf) VALUES (:razao_social, :endereco, :numero, :complemento, :bairro, :cep, :cidade, :cnpj, :email, :telefone, :inscricao_estadual, :status, :uf)');
        $stmt->execute([
            'razao_social' => $data['razao_social'],
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cep' => $data['cep'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'cnpj' => $data['cnpj'] ?? null,
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'inscricao_estadual' => $data['inscricao_estadual'] ?? null,
            'status' => $data['status'],
            'uf' => $data['uf'] ?? null,
        ]);
        http_response_code(201);
        echo json_encode(['id' => (int)$this->pdo->lastInsertId()]);
    }
}
