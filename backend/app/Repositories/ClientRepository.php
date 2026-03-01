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
        $stmt = $this->pdo->query('SELECT id, nome, endereco, numero, complemento, bairro, cep, cpf_cnpj, telefone, email, uf, status, cidade FROM clientes ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO clientes (nome, endereco, numero, complemento, bairro, cep, cpf_cnpj, telefone, email, uf, status, cidade) VALUES (:nome, :endereco, :numero, :complemento, :bairro, :cep, :cpf_cnpj, :telefone, :email, :uf, :status, :cidade)');
        $stmt->execute([
            'nome' => $data['nome'],
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cep' => $data['cep'] ?? null,
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'email' => $data['email'] ?? null,
            'uf' => $data['uf'] ?? null,
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
            'cidade' => $data['cidade'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM clientes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
