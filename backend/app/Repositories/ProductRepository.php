<?php
namespace App\Repositories;

use PDO;

class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /**
     * Update estoque_atual and recalculate custo_medio (weighted average) on purchase receive.
     */
    public function updateStockOnReceive(int $id, float $quantidade, float $valorUnitario): void
    {
        $stmt = $this->pdo->prepare('SELECT estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return;

        $estoqueAtual = (float)$row['estoque_atual'];
        $custoAtual   = (float)$row['custo_medio'];

        $novoEstoque  = $estoqueAtual + $quantidade;
        $novoCusto    = $novoEstoque > 0
            ? (($estoqueAtual * $custoAtual) + ($quantidade * $valorUnitario)) / $novoEstoque
            : $valorUnitario;

        $upStmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque_atual = :ea, custo_medio = :cm WHERE id = :id'
        );
        $upStmt->execute(['ea' => $novoEstoque, 'cm' => $novoCusto, 'id' => $id]);
    }

    /**
     * Decrease estoque_atual on sale delivery. Also decreases estoque_reservado proportionally.
     */
    public function updateStockOnDeliver(int $id, float $quantidade): void
    {
        $stmt = $this->pdo->prepare('SELECT estoque_atual, estoque_reservado FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return;

        $novoEstoque    = max(0, (float)$row['estoque_atual'] - $quantidade);
        $novaReserva    = max(0, (float)($row['estoque_reservado'] ?? 0) - $quantidade);

        $upStmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque_atual = :ea, estoque_reservado = :er WHERE id = :id'
        );
        $upStmt->execute(['ea' => $novoEstoque, 'er' => $novaReserva, 'id' => $id]);
    }

    /**
     * Reserve stock on sale creation (non-blocking — just tracks reservation).
     * Returns ['disponivel' => float, 'alerta' => bool].
     */
    public function reservarEstoque(int $id, float $quantidade): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT estoque_atual, COALESCE(estoque_reservado, 0) AS estoque_reservado FROM produtos WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return ['disponivel' => 0, 'alerta' => true];

        $disponivel = max(0, (float)$row['estoque_atual'] - (float)$row['estoque_reservado']);
        $alerta     = $quantidade > $disponivel;

        $upStmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque_reservado = estoque_reservado + :qty WHERE id = :id'
        );
        $upStmt->execute(['qty' => $quantidade, 'id' => $id]);

        return ['disponivel' => $disponivel, 'alerta' => $alerta];
    }

    /**
     * Release reserved stock (on cancellation).
     */
    public function liberarReserva(int $id, float $quantidade): void
    {
        $upStmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque_reservado = GREATEST(0, estoque_reservado - :qty) WHERE id = :id'
        );
        $upStmt->execute(['qty' => $quantidade, 'id' => $id]);
    }

}
