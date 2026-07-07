<?php
namespace App\Repositories;

use PDO;

class QuebraRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countAll(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM quebras')->fetchColumn();
    }

    public function fetchAll(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT q.id, q.produto_id, p.nome AS produto, p.unidade,
                    q.lote_id, q.quantidade, q.valor_unitario,
                    q.valor_total, q.tipo, q.observacao,
                    q.created_at, u.name AS usuario
             FROM quebras q
             JOIN produtos p ON p.id = q.produto_id
             LEFT JOIN users u ON u.id = q.usuario_id
             ORDER BY q.created_at DESC
             LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findProduto(int $produtoId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $produtoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO quebras (produto_id, lote_id, quantidade, valor_unitario, valor_total, tipo, observacao, usuario_id)
             VALUES (:pid, :lid, :qty, :vunit, :vtot, :tipo, :obs, :uid)'
        );
        $stmt->execute([
            'pid'   => $data['produto_id'],
            'lid'   => $data['lote_id'] ?? null,
            'qty'   => $data['quantidade'],
            'vunit' => $data['valor_unitario'],
            'vtot'  => $data['valor_total'],
            'tipo'  => $data['tipo'],
            'obs'   => $data['observacao'] ?? null,
            'uid'   => $data['usuario_id'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateProdutoEstoque(int $produtoId, float $saldoDepois): void
    {
        $stmt = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :s WHERE id = :id');
        $stmt->execute(['s' => $saldoDepois, 'id' => $produtoId]);
    }

    public function insertMovimentacaoEstoque(array $data): void
    {
        if (!$this->tableExists('movimentacoes_estoque')) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO movimentacoes_estoque
             (produto_id, tipo, quantidade, valor_unitario, saldo_antes, saldo_depois, referencia_id, referencia_tipo, observacao, usuario_id)
             VALUES (:pid, :tipo, :qty, :vunit, :sa, :sd, :rid, :rtp, :obs, :uid)'
        );
        $stmt->execute([
            'pid'  => $data['produto_id'],
            'tipo' => $data['tipo'],
            'qty'  => $data['quantidade'],
            'vunit'=> $data['valor_unitario'],
            'sa'   => $data['saldo_antes'],
            'sd'   => $data['saldo_depois'],
            'rid'  => $data['referencia_id'],
            'rtp'  => $data['referencia_tipo'],
            'obs'  => $data['observacao'] ?? null,
            'uid'  => $data['usuario_id'] ?? null,
        ]);
    }

    public function updateLoteQuantidade(int $loteId, float $quantidade): void
    {
        if (!$this->tableExists('lotes')) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE lotes SET quantidade_atual = GREATEST(0, quantidade_atual - :qty) WHERE id = :id'
        );
        $stmt->execute(['qty' => $quantidade, 'id' => $loteId]);
    }

    public function tableExists(string $table): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM {$table} LIMIT 1");
            $stmt->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
