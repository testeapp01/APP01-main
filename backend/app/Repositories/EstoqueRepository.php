<?php
namespace App\Repositories;

use PDO;

class EstoqueRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countMovimentacoes(array $filters): int
    {
        [$whereSql, $params] = $this->buildMovimentacoesFilters($filters);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM movimentacoes_estoque me
             JOIN produtos p ON p.id = me.produto_id
             {$whereSql}"
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function fetchMovimentacoes(array $filters, int $limit, int $offset): array
    {
        [$whereSql, $params] = $this->buildMovimentacoesFilters($filters);
        $sql = 
            "SELECT me.id, me.produto_id, p.nome AS produto, p.unidade,
                    me.tipo AS tipo_movimento, me.quantidade, me.valor_unitario,
                    me.saldo_antes, me.saldo_depois,
                    me.referencia_id, me.referencia_tipo, me.observacao,
                    me.created_at AS data
             FROM movimentacoes_estoque me
             JOIN produtos p ON p.id = me.produto_id
             {$whereSql}
             ORDER BY me.created_at DESC, me.id DESC
             LIMIT :lim OFFSET :off";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function fetchSaldos(string $query): array
    {
        $where = 'WHERE p.deleted_at IS NULL';
        $params = [];
        if ($query !== '') {
            $where .= ' AND p.nome LIKE :q';
            $params[':q'] = "%{$query}%";
        }

        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.nome, p.unidade, p.status,
                    p.estoque_atual,
                    p.estoque_reservado,
                    GREATEST(0, p.estoque_atual - p.estoque_reservado) AS disponivel,
                    p.custo_medio
             FROM produtos p
             {$where}
             ORDER BY p.nome ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findProduto(int $produtoId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateProdutoEstoque(int $produtoId, float $saldoDepois): void
    {
        $stmt = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :s WHERE id = :id');
        $stmt->execute(['s' => $saldoDepois, 'id' => $produtoId]);
    }

    public function insertMovimentacao(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO movimentacoes_estoque
             (produto_id, tipo, quantidade, valor_unitario, saldo_antes, saldo_depois, observacao, usuario_id)
             VALUES (:pid, :tipo, :qty, :vunit, :sa, :sd, :obs, :uid)'
        );
        $stmt->execute([
            'pid'  => $data['produto_id'],
            'tipo' => $data['tipo'],
            'qty'  => $data['quantidade'],
            'vunit'=> $data['valor_unitario'],
            'sa'   => $data['saldo_antes'],
            'sd'   => $data['saldo_depois'],
            'obs'  => $data['observacao'] ?? null,
            'uid'  => $data['usuario_id'] ?? null,
        ]);
    }

    private function buildMovimentacoesFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['tipo'])) {
            $conditions[] = 'me.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = 'p.nome LIKE :q';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        return [$whereSql, $params];
    }
}
