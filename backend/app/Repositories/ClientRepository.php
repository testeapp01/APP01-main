<?php
namespace App\Repositories;

use PDO;

class ClientRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, nome, cpf_cnpj, telefone, email, uf, status, cidade FROM clientes ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO clientes (nome, cpf_cnpj, telefone, email, uf, status, cidade) VALUES (:nome, :cpf_cnpj, :telefone, :email, :uf, :status, :cidade)');
        $stmt->execute([
            'nome' => $data['nome'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'email' => $data['email'] ?? null,
            'uf' => $data['uf'] ?? null,
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
            'cidade' => $data['cidade'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
