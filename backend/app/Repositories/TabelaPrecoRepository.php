<?php
namespace App\Repositories;

use PDO;

class TabelaPrecoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nome, tipo, desconto_percentual, ativa, created_at FROM tabelas_preco ORDER BY nome ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tabelas_preco (nome, tipo, desconto_percentual, ativa) VALUES (:nome, :tipo, :desc, :ativa)'
        );
        $stmt->execute([
            'nome' => $data['nome'],
            'tipo' => $data['tipo'],
            'desc' => $data['desconto_percentual'],
            'ativa' => $data['ativa'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): bool
    {
        $sets = [];
        $params = ['id' => $id];

        if (isset($fields['nome'])) {
            $sets[] = 'nome = :nome';
            $params['nome'] = $fields['nome'];
        }
        if (isset($fields['desconto_percentual'])) {
            $sets[] = 'desconto_percentual = :desc';
            $params['desc'] = (float)$fields['desconto_percentual'];
        }
        if (isset($fields['ativa'])) {
            $sets[] = 'ativa = :ativa';
            $params['ativa'] = (int)$fields['ativa'];
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE tabelas_preco SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
}
