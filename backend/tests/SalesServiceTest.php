<?php
use PHPUnit\Framework\TestCase;
use App\Repositories\SalesRepository;
use App\Repositories\ProductRepository;
use App\Services\SalesService;

final class SalesServiceTest extends TestCase
{
    private function createPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE produtos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, estoque_atual REAL DEFAULT 0, custo_medio REAL DEFAULT 0);");
        $pdo->exec("CREATE TABLE vendas (id INTEGER PRIMARY KEY AUTOINCREMENT, cliente_id INTEGER, produto_id INTEGER, quantidade REAL, valor_unitario REAL, receita_total REAL, custo_proporcional REAL, lucro_bruto REAL, margem_percentual REAL, status TEXT, data_venda DATETIME);");
        return $pdo;
    }

    public function testCreateAndDeliverSaleReducesStock(): void
    {
        $pdo = $this->createPdo();
        $pdo->prepare('INSERT INTO produtos (nome, estoque_atual, custo_medio) VALUES (:n, :e, :c)')->execute([':n'=>'Batata',':e'=>50,':c'=>2.0]);
        $productId = (int)$pdo->lastInsertId();

        $salesRepo = new SalesRepository($pdo);
        $productRepo = new ProductRepository($pdo);
        $service = new SalesService($salesRepo, $productRepo);

        $data = ['cliente_id'=>1,'produto_id'=>$productId,'quantidade'=>10,'valor_unitario'=>5.0];
        $id = $service->createSale($data);
        $this->assertIsInt($id);

        $res = $service->confirmDelivery($id);
        $this->assertEquals('ENTREGUE', $res['message']);
        $this->assertEquals(40.0, $res['novo_estoque']);
    }
}
