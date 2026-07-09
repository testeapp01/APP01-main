<?php
namespace App\Repositories;

use App\Helpers\Schema;
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

    public function count(array $filters): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = 'nome LIKE :q';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos {$whereSql}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function search(array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = 'nome LIKE :q';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT id, nome, tipo, unidade, custo_medio, status FROM produtos {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO produtos (nome, tipo, unidade, custo_medio, status) VALUES (:nome, :tipo, :unidade, :custo_medio, :status)');
        $stmt->execute([
            'nome' => $data['nome'],
            'tipo' => $data['tipo'] ?? null,
            'unidade' => $data['unidade'] ?? 'saco',
            'custo_medio' => $data['custo_medio'],
            'status' => $data['status'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = ['id' => $id];

        if (isset($data['nome'])) {
            $sets[] = 'nome = :nome';
            $params['nome'] = $data['nome'];
        }
        if (array_key_exists('tipo', $data)) {
            $sets[] = 'tipo = :tipo';
            $params['tipo'] = $data['tipo'];
        }
        if (array_key_exists('unidade', $data)) {
            $sets[] = 'unidade = :unidade';
            $params['unidade'] = $data['unidade'];
        }
        if (array_key_exists('custo_medio', $data)) {
            $sets[] = 'custo_medio = :custo_medio';
            $params['custo_medio'] = $data['custo_medio'];
        }
        if (array_key_exists('status', $data)) {
            $sets[] = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE produtos SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
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
        $stmt = $this->pdo->prepare('SELECT estoque_atual FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return;

        $novoEstoque = max(0, (float)$row['estoque_atual'] - $quantidade);

        if (Schema::hasColumn($this->pdo, 'produtos', 'estoque_reservado')) {
            $reservaStmt = $this->pdo->prepare('SELECT estoque_reservado FROM produtos WHERE id = :id LIMIT 1');
            $reservaStmt->execute(['id' => $id]);
            $reserva = (float)($reservaStmt->fetchColumn() ?: 0);
            $novaReserva = max(0, $reserva - $quantidade);
            $upStmt = $this->pdo->prepare(
                'UPDATE produtos SET estoque_atual = :ea, estoque_reservado = :er WHERE id = :id'
            );
            $upStmt->execute(['ea' => $novoEstoque, 'er' => $novaReserva, 'id' => $id]);
            return;
        }

        $upStmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque_atual = :ea WHERE id = :id'
        );
        $upStmt->execute(['ea' => $novoEstoque, 'id' => $id]);
    }

    /**
     * Reserve stock on sale creation (non-blocking — just tracks reservation).
     * Returns ['disponivel' => float, 'alerta' => bool].
     */
    public function reservarEstoque(int $id, float $quantidade): array
    {
        if (!Schema::hasColumn($this->pdo, 'produtos', 'estoque_reservado')) {
            $stmt = $this->pdo->prepare('SELECT estoque_atual FROM produtos WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return ['disponivel' => 0, 'alerta' => true];

            $disponivel = max(0, (float)$row['estoque_atual']);
            return ['disponivel' => $disponivel, 'alerta' => $quantidade > $disponivel];
        }

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
        if (!Schema::hasColumn($this->pdo, 'produtos', 'estoque_reservado')) {
            return;
        }

        $upStmt = $this->pdo->prepare(
            'UPDATE produtos SET estoque_reservado = GREATEST(0, estoque_reservado - :qty) WHERE id = :id'
        );
        $upStmt->execute(['qty' => $quantidade, 'id' => $id]);
    }

}
