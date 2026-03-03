<?php
namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\SalesRepository;
use PDO;

class SalesCreationService
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasCabecalhoColumnsCache = null;
    private ?bool $hasVendasCabecalhoCache = null;
    private ?bool $hasStatusPedidoCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): array
    {
        $statusPedido = $this->normalizeStatusPedidoLabel($data['status'] ?? null);
        $data['status'] = $statusPedido;

        if (empty($data['cliente_id']) && !empty($data['cliente'])) {
            $stmt = $this->pdo->prepare('SELECT id FROM clientes WHERE nome = :nome LIMIT 1');
            $stmt->execute([':nome' => $data['cliente']]);
            $cli = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cli) {
                $data['cliente_id'] = $cli['id'];
            }
        }

        if (empty($data['cliente_id'])) {
            throw new \RuntimeException('cliente_id obrigatório', 400);
        }

        $tipoCabecalho = 'venda';
        $data['data_envio_prevista'] = !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null;
        $data['data_entrega_prevista'] = !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null;

        $service = new SalesService(
            new SalesRepository($this->pdo),
            new ProductRepository($this->pdo)
        );

        $createdIds = [];
        if (!empty($data['items']) && is_array($data['items'])) {
            $valorTotalCabecalho = 0.0;
            foreach ($data['items'] as $item) {
                if (!empty($item['produto_id'])) {
                    $valorTotalCabecalho += (float)($item['quantidade'] ?? 0) * (float)($item['valor_unitario'] ?? 0);
                }
            }

            $cabecalhoId = null;
            if ($this->hasVendasCabecalhoTable() && $this->hasVendasColumn('venda_cabecalho_id')) {
                $cabecalhoId = $this->createHeader(
                    (int)$data['cliente_id'],
                    $tipoCabecalho,
                    $valorTotalCabecalho,
                    $data['data_envio_prevista'],
                    $data['data_entrega_prevista'],
                    $statusPedido
                );
            }

            foreach ($data['items'] as $item) {
                if (empty($item['produto_id'])) {
                    continue;
                }
                $saleData = [
                    'venda_cabecalho_id' => $cabecalhoId,
                    'cliente_id' => $data['cliente_id'],
                    'produto_id' => (int)$item['produto_id'],
                    'quantidade' => (float)($item['quantidade'] ?? 0),
                    'valor_unitario' => (float)($item['valor_unitario'] ?? 0),
                    'status' => $statusPedido,
                    'data_envio_prevista' => $data['data_envio_prevista'],
                    'data_entrega_prevista' => $data['data_entrega_prevista'],
                ];
                $createdIds[] = $service->createSale($saleData);
            }

            if (empty($createdIds)) {
                throw new \RuntimeException('Adicione ao menos um item válido com produto_id', 400);
            }

            return [
                'id' => $createdIds[0],
                'ids' => $createdIds,
                'cabecalho_id' => $cabecalhoId,
            ];
        }

        if (empty($data['produto_id'])) {
            $prodName = $data['produto'] ?? ($data['nome_produto'] ?? null);
            if ($prodName) {
                $stmt = $this->pdo->prepare('SELECT id FROM produtos WHERE nome = :nome LIMIT 1');
                $stmt->execute([':nome' => $prodName]);
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($produto) {
                    $data['produto_id'] = $produto['id'];
                }
            }
        }

        if (empty($data['produto_id'])) {
            throw new \RuntimeException('produto_id ou nome_produto obrigatório', 400);
        }

        $cabecalhoId = null;
        if ($this->hasVendasCabecalhoTable() && $this->hasVendasColumn('venda_cabecalho_id')) {
            $cabecalhoId = $this->createHeader(
                (int)$data['cliente_id'],
                $tipoCabecalho,
                (float)($data['quantidade'] ?? 0) * (float)($data['valor_unitario'] ?? 0),
                $data['data_envio_prevista'],
                $data['data_entrega_prevista'],
                $statusPedido
            );
            $data['venda_cabecalho_id'] = $cabecalhoId;
        }

        $id = $service->createSale($data);
        $sale = (new SalesRepository($this->pdo))->findById((int)$id);

        return [
            'id' => $id,
            'sale' => $sale,
            'cabecalho_id' => $cabecalhoId,
        ];
    }

    private function createHeader(
        int $clienteId,
        string $tipo,
        float $valorTotal,
        ?string $dataInicio,
        ?string $dataFim,
        string $status
    ): int {
        $statusNormalizado = $this->normalizeStatusPedidoLabel($status);
        $params = [
            'tipo' => $tipo,
            'cliente_id' => $clienteId,
            'valor_total' => $valorTotal,
            'data_inicio_prevista' => $dataInicio,
            'data_fim_prevista' => $dataFim,
            'status' => $statusNormalizado,
        ];

        if ($this->hasVendasCabecalhoColumn('id_statuspedido')) {
            $statusId = $this->statusPedidoIdByNome($statusNormalizado)
                ?? ($statusNormalizado === 'ENTREGUE' ? 2 : 1);
            $stmt = $this->pdo->prepare(
                'INSERT INTO vendas_cabecalho (tipo, cliente_id, valor_total, data_inicio_prevista, data_fim_prevista, status, id_statuspedido)
                 VALUES (:tipo, :cliente_id, :valor_total, :data_inicio_prevista, :data_fim_prevista, :status, :id_statuspedido)'
            );
            $params['id_statuspedido'] = $statusId;
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO vendas_cabecalho (tipo, cliente_id, valor_total, data_inicio_prevista, data_fim_prevista, status)
                 VALUES (:tipo, :cliente_id, :valor_total, :data_inicio_prevista, :data_fim_prevista, :status)'
            );
        }

        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    private function normalizeStatusPedidoLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'ENTREGUE' ? 'ENTREGUE' : 'AGUARDANDO';
    }

    private function statusPedidoIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusPedidoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_pedido WHERE UPPER(nome) = :nome LIMIT 1');
        $stmt->execute(['nome' => strtoupper($nome)]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    private function hasStatusPedidoTable(): bool
    {
        if ($this->hasStatusPedidoCache !== null) {
            return $this->hasStatusPedidoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='status_pedido' LIMIT 1");
                $stmt->execute();
                $this->hasStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasStatusPedidoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'status_pedido'");
            $this->hasStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasStatusPedidoCache;
        } catch (\Throwable $e) {
            $this->hasStatusPedidoCache = false;
            return false;
        }
    }

    private function vendasColumns(): array
    {
        if ($this->vendasColumnsCache !== null) {
            return $this->vendasColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(vendas)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));

            return $this->vendasColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM vendas');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->vendasColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->vendasColumnsCache;
    }

    private function hasVendasColumn(string $column): bool
    {
        return in_array($column, $this->vendasColumns(), true);
    }

    private function vendasCabecalhoColumns(): array
    {
        if ($this->vendasCabecalhoColumnsCache !== null) {
            return $this->vendasCabecalhoColumnsCache;
        }

        if (!$this->hasVendasCabecalhoTable()) {
            $this->vendasCabecalhoColumnsCache = [];
            return $this->vendasCabecalhoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(vendas_cabecalho)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM vendas_cabecalho');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->vendasCabecalhoColumnsCache;
    }

    private function hasVendasCabecalhoColumn(string $column): bool
    {
        return in_array($column, $this->vendasCabecalhoColumns(), true);
    }

    private function hasVendasCabecalhoTable(): bool
    {
        if ($this->hasVendasCabecalhoCache !== null) {
            return $this->hasVendasCabecalhoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='vendas_cabecalho' LIMIT 1");
                $stmt->execute();
                $this->hasVendasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasVendasCabecalhoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'vendas_cabecalho'");
            $this->hasVendasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasVendasCabecalhoCache;
        } catch (\Throwable $e) {
            $this->hasVendasCabecalhoCache = false;
            return false;
        }
    }
}