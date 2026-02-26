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

    public function updateStockAndCost(int $id, float $novoEstoque, float $novoCusto): bool
    {
        $stmt = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :estoque, custo_medio = :custo WHERE id = :id');
        return $stmt->execute(['estoque' => $novoEstoque, 'custo' => $novoCusto, 'id' => $id]);
    }
}
