<?php
namespace App\Repositories;

use PDO;

class LoteRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function fetchAll(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['produto_id'])) {
            $where[] = 'l.produto_id = :pid';
            $params[':pid'] = $filters['produto_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'l.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare(
            "SELECT l.*, p.nome AS produto_nome, p.unidade,
                    f.razao_social AS fornecedor_nome,
                    DATEDIFF(l.data_validade, CURDATE()) AS dias_para_vencer
             FROM lotes l
             JOIN produtos p ON p.id = l.produto_id
             LEFT JOIN fornecedores f ON f.id = l.fornecedor_id
             {$whereSql}
             ORDER BY l.data_validade ASC, l.id ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO lotes
             (produto_id, fornecedor_id, compra_cabecalho_id, codigo_lote, data_validade,
              data_colheita, origem, quantidade_entrada, quantidade_atual, custo_unitario, status)
             VALUES (:pid, :fid, :cid, :cod, :dv, :dc, :orig, :qe, :qa, :cu, :st)'
        );
        $stmt->execute([
            'pid' => $data['produto_id'],
            'fid' => $data['fornecedor_id'] ?? null,
            'cid' => $data['compra_cabecalho_id'] ?? null,
            'cod' => $data['codigo_lote'] ?? null,
            'dv'  => $data['data_validade'],
            'dc'  => $data['data_colheita'] ?? null,
            'orig'=> $data['origem'] ?? null,
            'qe'  => $data['quantidade_entrada'],
            'qa'  => $data['quantidade_entrada'],
            'cu'  => $data['custo_unitario'] ?? 0,
            'st'  => $data['status'] ?? 'ativo',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): bool
    {
        $sets = [];
        $params = ['id' => $id];

        if (isset($fields['status'])) {
            $sets[] = 'status = :status';
            $params['status'] = $fields['status'];
        }
        if (isset($fields['quantidade_atual'])) {
            $sets[] = 'quantidade_atual = :qa';
            $params['qa'] = (float)$fields['quantidade_atual'];
        }
        if (isset($fields['data_validade'])) {
            $sets[] = 'data_validade = :dv';
            $params['dv'] = $fields['data_validade'];
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE lotes SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
}
