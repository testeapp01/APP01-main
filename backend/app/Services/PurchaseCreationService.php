<?php
namespace App\Services;

use PDO;

class PurchaseCreationService
{
    private ?array $comprasColumnsCache = null;
    private ?array $comprasCabecalhoColumnsCache = null;
    private ?array $comprasStatusEnumValuesCache = null;
    private ?bool $hasComprasCabecalhoCache = null;
    private ?bool $hasStatusCompraCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): array
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            throw new \RuntimeException('Estrutura de compras por cabeçalho não disponível. Execute as migrations.', 500);
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \RuntimeException('items obrigatório', 400);
        }

        if (empty($data['fornecedor_id'])) {
            throw new \RuntimeException('fornecedor_id obrigatório', 400);
        }

        $tipoOperacao = ($data['tipo_operacao'] ?? (($data['tipo'] ?? 'revenda') === 'venda' ? 'venda' : 'revenda'));
        if ($tipoOperacao === 'venda' && empty($data['cliente_id'])) {
            throw new \RuntimeException('Selecione um cliente para compras do tipo venda', 400);
        }
        if ($tipoOperacao === 'venda' && empty($data['motorista_id'])) {
            throw new \RuntimeException('Selecione um motorista para compras do tipo venda', 400);
        }

        $validItems = [];
        $valorTotalCabecalho = 0.0;
        foreach (($data['items'] ?? []) as $item) {
            if (empty($item['produto_id'])) {
                continue;
            }
            $qtd = (float)($item['quantidade'] ?? 0);
            $vu = (float)($item['valor_unitario'] ?? 0);
            if ($qtd <= 0 || $vu <= 0) {
                continue;
            }
            $validItems[] = [
                'produto_id' => (int)$item['produto_id'],
                'quantidade' => $qtd,
                'valor_unitario' => $vu,
            ];
            $valorTotalCabecalho += $qtd * $vu;
        }

        if (empty($validItems)) {
            throw new \RuntimeException('Adicione ao menos um item válido com produto_id, quantidade e valor_unitario', 400);
        }

        $status = $this->normalizeStatusCompraLabel($data['status'] ?? null);
        $itemStatus = $this->normalizeCompraItemStatusForSchema($status);
        $dataEnvio = !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null;
        $dataEntrega = !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null;

        $cabecalhoId = $this->createHeader(
            (int)$data['fornecedor_id'],
            $tipoOperacao,
            !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
            $valorTotalCabecalho,
            $dataEnvio,
            $dataEntrega,
            $status
        );

        $createdIds = [];
        foreach ($validItems as $item) {
            $calcs = CommissionService::calculate(
                $item['quantidade'],
                $item['valor_unitario'],
                $data['tipo_comissao'] ?? null,
                isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null,
                isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0
            );

            $possible = [
                'compra_cabecalho_id' => $cabecalhoId,
                'fornecedor_id' => (int)$data['fornecedor_id'],
                'produto_id' => $item['produto_id'],
                'motorista_id' => !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
                'tipo_operacao' => $tipoOperacao,
                'cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'],
                'tipo_comissao' => $data['tipo_comissao'] ?? null,
                'valor_comissao' => isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null,
                'extra_por_saco' => isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0,
                'custo_total' => $calcs['valor_total'],
                'comissao_total' => $calcs['comissao_total'],
                'custo_final_real' => $calcs['custo_final_real'],
                'status' => $itemStatus,
                'data_envio_prevista' => $dataEnvio,
                'data_entrega_prevista' => $dataEntrega,
            ];

            $columns = array_values(array_filter(array_keys($possible), fn(string $column) => $this->hasComprasColumn($column)));
            $valuesSql = [];
            $params = [];

            foreach ($columns as $column) {
                $valuesSql[] = ':' . $column;
                $params[$column] = $possible[$column];
            }

            if ($this->hasComprasColumn('data_compra')) {
                $columns[] = 'data_compra';
                $valuesSql[] = 'NOW()';
            }

            try {
                $stmt = $this->pdo->prepare('INSERT INTO compras (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $valuesSql) . ')');
                $stmt->execute($params);
            } catch (\PDOException $e) {
                $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
                if ($mysqlCode === 1452) {
                    throw new \RuntimeException('Fornecedor, produto, cliente ou motorista inválido.', 400);
                }

                throw new \RuntimeException('Falha ao salvar itens da compra.', 500);
            }

            $createdIds[] = (int)$this->pdo->lastInsertId();
        }

        return [
            'id' => $createdIds[0] ?? null,
            'ids' => $createdIds,
            'cabecalho_id' => $cabecalhoId,
            'status' => $status,
        ];
    }

    private function createHeader(
        int $fornecedorId,
        string $tipoOperacao,
        ?int $clienteId,
        ?int $motoristaId,
        float $valorTotal,
        ?string $dataEnvio,
        ?string $dataEntrega,
        string $status
    ): int {
        $statusNormalizado = $this->normalizeStatusCompraLabel($status);
        $params = [
            'tipo_operacao' => $tipoOperacao,
            'fornecedor_id' => $fornecedorId,
            'cliente_id' => $clienteId,
            'motorista_id' => $motoristaId,
            'valor_total' => $valorTotal,
            'data_envio_prevista' => $dataEnvio,
            'data_entrega_prevista' => $dataEntrega,
            'status' => $statusNormalizado,
        ];

        if ($this->hasComprasCabecalhoColumn('id_statuscompra')) {
            $statusId = $this->statusCompraIdByNome($statusNormalizado)
                ?? ($statusNormalizado === 'RECEBIDA' ? 2 : 1);
            $stmt = $this->pdo->prepare(
                'INSERT INTO compras_cabecalho (tipo_operacao, fornecedor_id, cliente_id, motorista_id, valor_total, data_envio_prevista, data_entrega_prevista, status, id_statuscompra)
                 VALUES (:tipo_operacao, :fornecedor_id, :cliente_id, :motorista_id, :valor_total, :data_envio_prevista, :data_entrega_prevista, :status, :id_statuscompra)'
            );
            $params['id_statuscompra'] = $statusId;
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO compras_cabecalho (tipo_operacao, fornecedor_id, cliente_id, motorista_id, valor_total, data_envio_prevista, data_entrega_prevista, status)
                 VALUES (:tipo_operacao, :fornecedor_id, :cliente_id, :motorista_id, :valor_total, :data_envio_prevista, :data_entrega_prevista, :status)'
            );
        }

        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    private function normalizeStatusCompraLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'RECEBIDA' ? 'RECEBIDA' : 'AGUARDANDO';
    }

    private function normalizeCompraItemStatusForSchema(string $status): string
    {
        if ($status === 'RECEBIDA') {
            return 'RECEBIDA';
        }

        $allowed = $this->comprasStatusEnumValues();
        if (empty($allowed)) {
            return 'AGUARDANDO';
        }

        if (in_array('AGUARDANDO', $allowed, true)) {
            return 'AGUARDANDO';
        }

        if (in_array('NEGOCIADA', $allowed, true)) {
            return 'NEGOCIADA';
        }

        return $allowed[0] ?? 'AGUARDANDO';
    }

    private function comprasStatusEnumValues(): array
    {
        if ($this->comprasStatusEnumValuesCache !== null) {
            return $this->comprasStatusEnumValuesCache;
        }

        $this->comprasStatusEnumValuesCache = [];
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return $this->comprasStatusEnumValuesCache;
        }

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM compras LIKE 'status'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $type = (string)($row['Type'] ?? '');
            if (preg_match("/^enum\\((.+)\\)$/i", $type, $m) !== 1) {
                return $this->comprasStatusEnumValuesCache;
            }

            $parts = str_getcsv($m[1], ',', "'", "\\");
            $this->comprasStatusEnumValuesCache = array_values(array_filter(array_map(
                static fn(string $value): string => strtoupper(trim($value)),
                $parts
            )));
        } catch (\Throwable $e) {
            $this->comprasStatusEnumValuesCache = [];
        }

        return $this->comprasStatusEnumValuesCache;
    }

    private function statusCompraIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusCompraTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_compra WHERE UPPER(nome) = :nome LIMIT 1');
        $stmt->execute(['nome' => strtoupper($nome)]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    private function hasStatusCompraTable(): bool
    {
        if ($this->hasStatusCompraCache !== null) {
            return $this->hasStatusCompraCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='status_compra' LIMIT 1");
                $stmt->execute();
                $this->hasStatusCompraCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasStatusCompraCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'status_compra'");
            $this->hasStatusCompraCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasStatusCompraCache;
        } catch (\Throwable $e) {
            $this->hasStatusCompraCache = false;
            return false;
        }
    }

    private function comprasColumns(): array
    {
        if ($this->comprasColumnsCache !== null) {
            return $this->comprasColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(compras)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->comprasColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));

            return $this->comprasColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM compras');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->comprasColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->comprasColumnsCache;
    }

    private function hasComprasColumn(string $column): bool
    {
        return in_array($column, $this->comprasColumns(), true);
    }

    private function comprasCabecalhoColumns(): array
    {
        if ($this->comprasCabecalhoColumnsCache !== null) {
            return $this->comprasCabecalhoColumnsCache;
        }

        if (!$this->hasComprasCabecalhoTable()) {
            $this->comprasCabecalhoColumnsCache = [];
            return $this->comprasCabecalhoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(compras_cabecalho)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->comprasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM compras_cabecalho');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->comprasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->comprasCabecalhoColumnsCache;
    }

    private function hasComprasCabecalhoColumn(string $column): bool
    {
        return in_array($column, $this->comprasCabecalhoColumns(), true);
    }

    private function hasComprasCabecalhoTable(): bool
    {
        if ($this->hasComprasCabecalhoCache !== null) {
            return $this->hasComprasCabecalhoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='compras_cabecalho' LIMIT 1");
                $stmt->execute();
                $this->hasComprasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasComprasCabecalhoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'compras_cabecalho'");
            $this->hasComprasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasComprasCabecalhoCache;
        } catch (\Throwable $e) {
            $this->hasComprasCabecalhoCache = false;
            return false;
        }
    }
}