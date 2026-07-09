<?php
require 'vendor/autoload.php';
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
$controller = new App\Controllers\SalesController($pdo);
ob_start();
$controller->deliver();
$raw = ob_get_clean();
var_dump($raw);
