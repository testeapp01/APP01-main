<?php
namespace App\Repositories;

use PDO;

class SalesRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO vendas (cliente_id, produto_id, quantidade, valor_unitario, receita_total, custo_proporcional, lucro_bruto, margem_percentual, status, data_venda) VALUES (:cliente_id, :produto_id, :quantidade, :valor_unitario, :receita_total, :custo_proporcional, :lucro_bruto, :margem_percentual, :status, CURRENT_TIMESTAMP)');
        $stmt->execute([
            'cliente_id' => $data['cliente_id'],
            'produto_id' => $data['produto_id'],
            'quantidade' => $data['quantidade'],
            'valor_unitario' => $data['valor_unitario'],
            'receita_total' => $data['receita_total'],
            'custo_proporcional' => $data['custo_proporcional'],
            'lucro_bruto' => $data['lucro_bruto'],
            'margem_percentual' => $data['margem_percentual'],
            'status' => $data['status'] ?? 'ORCAMENTO',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vendas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE vendas SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
