<?php
namespace App\Repositories;

use PDO;

class PurchaseRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM compras WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO compras (fornecedor_id, produto_id, motorista_id, quantidade, valor_unitario, tipo_comissao, valor_comissao, extra_por_saco, custo_total, comissao_total, custo_final_real, status, data_compra) VALUES (:fornecedor_id, :produto_id, :motorista_id, :quantidade, :valor_unitario, :tipo_comissao, :valor_comissao, :extra_por_saco, :custo_total, :comissao_total, :custo_final_real, :status, CURRENT_TIMESTAMP)');
        $stmt->execute([
            'fornecedor_id' => $data['fornecedor_id'],
            'produto_id' => $data['produto_id'],
            'motorista_id' => $data['motorista_id'],
            'quantidade' => $data['quantidade'],
            'valor_unitario' => $data['valor_unitario'],
            'tipo_comissao' => $data['tipo_comissao'],
            'valor_comissao' => $data['valor_comissao'],
            'extra_por_saco' => $data['extra_por_saco'],
            'custo_total' => $data['custo_total'],
            'comissao_total' => $data['comissao_total'],
            'custo_final_real' => $data['custo_final_real'],
            'status' => $data['status'] ?? 'NEGOCIADA',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE compras SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
