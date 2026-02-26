<?php
use PHPUnit\Framework\TestCase;
use App\Repositories\PurchaseRepository;
use App\Repositories\ProductRepository;
use App\Services\PurchaseService;

final class PurchaseServiceTest extends TestCase
{
    private function createPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE produtos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, estoque_atual REAL DEFAULT 0, custo_medio REAL DEFAULT 0);");
        $pdo->exec("CREATE TABLE compras (id INTEGER PRIMARY KEY AUTOINCREMENT, fornecedor_id INTEGER, produto_id INTEGER, motorista_id INTEGER, quantidade REAL, valor_unitario REAL, tipo_comissao TEXT, valor_comissao REAL, extra_por_saco REAL, custo_total REAL, comissao_total REAL, custo_final_real REAL, status TEXT, data_compra DATETIME);");
        return $pdo;
    }

    public function testCreateAndReceiveUpdatesStock(): void
    {
        $pdo = $this->createPdo();
        // insert product
        $pdo->prepare('INSERT INTO produtos (nome, estoque_atual, custo_medio) VALUES (:n, :e, :c)')->execute([':n'=>'Batata',':e'=>0,':c'=>0]);
        $productId = (int)$pdo->lastInsertId();

        $purchaseRepo = new PurchaseRepository($pdo);
        $productRepo = new ProductRepository($pdo);
        $service = new PurchaseService($purchaseRepo, $productRepo);

        $data = ['fornecedor_id'=>1,'produto_id'=>$productId,'motorista_id'=>1,'quantidade'=>100.0,'valor_unitario'=>2.0,'tipo_comissao'=>'percentual','valor_comissao'=>5.0,'extra_por_saco'=>0.5,'custo_total'=>200.0,'comissao_total'=>10.0,'custo_final_real'=>210.0,'status'=>'NEGOCIADA'];
        $id = $service->createPurchase($data);
        $this->assertIsInt($id);

        $res = $service->receivePurchase($id);
        $this->assertEquals('RECEBIDA', $res['message']);
        $this->assertEquals(100.0, $res['novo_estoque']);
        $this->assertGreaterThan(0, $res['novo_custo_medio']);
    }
}
