<?php
use PHPUnit\Framework\TestCase;
use App\Controllers\SalesController;
use App\Controllers\PurchaseController;

final class StatusHistoryIntegrationTest extends TestCase
{
    public function testSalesHeaderDeliveryRegistersHistoryWithUser(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, role TEXT);");
        $pdo->exec("CREATE TABLE produtos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, estoque_atual REAL DEFAULT 0, custo_medio REAL DEFAULT 0);");
        $pdo->exec("CREATE TABLE status_pedido (id INTEGER PRIMARY KEY, nome TEXT);");
        $pdo->exec("CREATE TABLE vendas_cabecalho (id INTEGER PRIMARY KEY AUTOINCREMENT, tipo TEXT, cliente_id INTEGER, valor_total REAL, status TEXT, id_statuspedido INTEGER, data_inicio_prevista TEXT, data_fim_prevista TEXT);");
        $pdo->exec("CREATE TABLE vendas (id INTEGER PRIMARY KEY AUTOINCREMENT, venda_cabecalho_id INTEGER, cliente_id INTEGER, produto_id INTEGER, quantidade REAL, valor_unitario REAL, receita_total REAL, custo_proporcional REAL, lucro_bruto REAL, margem_percentual REAL, status TEXT, data_venda TEXT);");
        $pdo->exec("CREATE TABLE historico_status_pedido (id INTEGER PRIMARY KEY AUTOINCREMENT, venda_cabecalho_id INTEGER, usuario_id INTEGER, id_statuspedido INTEGER, snapshot_json TEXT, confirmado_em TEXT);");

        $pdo->exec("INSERT INTO users (id, name, email, password, role) VALUES (7, 'Admin', 'admin@example.com', 'x', 'admin')");
        $pdo->exec("INSERT INTO produtos (id, nome, estoque_atual, custo_medio) VALUES (1, 'Batata', 100, 2)");
        $pdo->exec("INSERT INTO status_pedido (id, nome) VALUES (1, 'AGUARDANDO'), (2, 'ENTREGUE')");
        $pdo->exec("INSERT INTO vendas_cabecalho (id, tipo, cliente_id, valor_total, status, id_statuspedido) VALUES (1, 'venda', 1, 80, 'AGUARDANDO', 1)");
        $pdo->exec("INSERT INTO vendas (id, venda_cabecalho_id, cliente_id, produto_id, quantidade, valor_unitario, receita_total, custo_proporcional, lucro_bruto, margem_percentual, status, data_venda) VALUES (1, 1, 1, 1, 10, 8, 80, 20, 60, 75, 'AGUARDANDO', CURRENT_TIMESTAMP)");

        $GLOBALS['AUTH_USER'] = ['sub' => 7];
        $GLOBALS['SANITIZED_INPUT'] = ['venda_cabecalho_id' => 1];

        $controller = new SalesController($pdo);
        ob_start();
        $controller->deliver();
        $raw = (string)ob_get_clean();
        $body = json_decode($raw, true);

        $this->assertSame('ENTREGUE', $body['message'] ?? null);

        $stmt = $pdo->query("SELECT usuario_id, id_statuspedido, snapshot_json FROM historico_status_pedido WHERE venda_cabecalho_id = 1 ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertSame('7', (string)$row['usuario_id']);
        $this->assertSame('2', (string)$row['id_statuspedido']);
        $this->assertNotEmpty($row['snapshot_json']);

        unset($GLOBALS['AUTH_USER'], $GLOBALS['SANITIZED_INPUT']);
    }

    public function testPurchaseHeaderDeliveryRegistersHistoryWithUser(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, role TEXT);");
        $pdo->exec("CREATE TABLE produtos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, estoque_atual REAL DEFAULT 0, custo_medio REAL DEFAULT 0);");
        $pdo->exec("CREATE TABLE status_compra (id INTEGER PRIMARY KEY, nome TEXT);");
        $pdo->exec("CREATE TABLE compras_cabecalho (id INTEGER PRIMARY KEY AUTOINCREMENT, tipo_operacao TEXT, fornecedor_id INTEGER, cliente_id INTEGER, motorista_id INTEGER, valor_total REAL, status TEXT, id_statuscompra INTEGER, data_envio_prevista TEXT, data_entrega_prevista TEXT);");
        $pdo->exec("CREATE TABLE compras (id INTEGER PRIMARY KEY AUTOINCREMENT, compra_cabecalho_id INTEGER, fornecedor_id INTEGER, produto_id INTEGER, motorista_id INTEGER, quantidade REAL, valor_unitario REAL, comissao_total REAL DEFAULT 0, status TEXT, data_compra TEXT);");
        $pdo->exec("CREATE TABLE historico_status_compra (id INTEGER PRIMARY KEY AUTOINCREMENT, compra_cabecalho_id INTEGER, usuario_id INTEGER, id_statuscompra INTEGER, snapshot_json TEXT, confirmado_em TEXT);");

        $pdo->exec("INSERT INTO users (id, name, email, password, role) VALUES (9, 'Operador', 'operador@example.com', 'x', 'operador')");
        $pdo->exec("INSERT INTO produtos (id, nome, estoque_atual, custo_medio) VALUES (1, 'Cebola', 0, 0)");
        $pdo->exec("INSERT INTO status_compra (id, nome) VALUES (1, 'AGUARDANDO'), (2, 'RECEBIDA')");
        $pdo->exec("INSERT INTO compras_cabecalho (id, tipo_operacao, fornecedor_id, valor_total, status, id_statuscompra) VALUES (1, 'revenda', 1, 20, 'AGUARDANDO', 1)");
        $pdo->exec("INSERT INTO compras (id, compra_cabecalho_id, fornecedor_id, produto_id, motorista_id, quantidade, valor_unitario, comissao_total, status, data_compra) VALUES (1, 1, 1, 1, 1, 5, 4, 0, 'AGUARDANDO', CURRENT_TIMESTAMP)");

        $GLOBALS['AUTH_USER'] = ['sub' => 9];

        $controller = new PurchaseController($pdo);
        ob_start();
        $controller->confirmHeaderDelivery(1);
        $raw = (string)ob_get_clean();
        $body = json_decode($raw, true);

        $this->assertSame('Entrega confirmada com sucesso', $body['message'] ?? null);

        $stmt = $pdo->query("SELECT usuario_id, id_statuscompra, snapshot_json FROM historico_status_compra WHERE compra_cabecalho_id = 1 ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertSame('9', (string)$row['usuario_id']);
        $this->assertSame('2', (string)$row['id_statuscompra']);
        $this->assertNotEmpty($row['snapshot_json']);

        unset($GLOBALS['AUTH_USER']);
    }

    public function testPurchaseHeaderUpdateCannotFinalizeDirectly(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE compras_cabecalho (id INTEGER PRIMARY KEY AUTOINCREMENT, tipo_operacao TEXT, fornecedor_id INTEGER, cliente_id INTEGER, motorista_id INTEGER, valor_total REAL, status TEXT, id_statuscompra INTEGER, data_envio_prevista TEXT, data_entrega_prevista TEXT);");
        $pdo->exec("CREATE TABLE compras (id INTEGER PRIMARY KEY AUTOINCREMENT, compra_cabecalho_id INTEGER, status TEXT);");
        $pdo->exec("INSERT INTO compras_cabecalho (id, tipo_operacao, fornecedor_id, valor_total, status, id_statuscompra) VALUES (1, 'revenda', 1, 20, 'AGUARDANDO', 1)");

        $GLOBALS['SANITIZED_INPUT'] = ['status' => 'RECEBIDA'];

        $controller = new PurchaseController($pdo);
        ob_start();
        $controller->updateHeader(1);
        $raw = (string)ob_get_clean();
        $body = json_decode($raw, true);

        $this->assertSame('Use a confirmação de entrega para finalizar o pedido de compra', $body['error'] ?? null);

        unset($GLOBALS['SANITIZED_INPUT']);
    }

    public function testPurchaseHeaderDeleteBlockedWhenReceived(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE compras_cabecalho (id INTEGER PRIMARY KEY AUTOINCREMENT, tipo_operacao TEXT, fornecedor_id INTEGER, cliente_id INTEGER, motorista_id INTEGER, valor_total REAL, status TEXT, id_statuscompra INTEGER, data_envio_prevista TEXT, data_entrega_prevista TEXT);");
        $pdo->exec("CREATE TABLE compras (id INTEGER PRIMARY KEY AUTOINCREMENT, compra_cabecalho_id INTEGER, status TEXT);");
        $pdo->exec("INSERT INTO compras_cabecalho (id, tipo_operacao, fornecedor_id, valor_total, status, id_statuscompra) VALUES (1, 'revenda', 1, 20, 'RECEBIDA', 2)");
        $pdo->exec("INSERT INTO compras (id, compra_cabecalho_id, status) VALUES (1, 1, 'RECEBIDA')");

        $controller = new PurchaseController($pdo);
        ob_start();
        $controller->deleteHeader(1);
        $raw = (string)ob_get_clean();
        $body = json_decode($raw, true);

        $this->assertSame('Não é permitido excluir pedido de compra recebido', $body['error'] ?? null);

        $count = (int)$pdo->query('SELECT COUNT(*) FROM compras_cabecalho WHERE id = 1')->fetchColumn();
        $this->assertSame(1, $count);
    }
}
