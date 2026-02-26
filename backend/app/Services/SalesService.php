<?php
namespace App\Services;

use App\Repositories\SalesRepository;
use App\Repositories\ProductRepository;
use App\Logger\Logger;

class SalesService
{
    public function __construct(private SalesRepository $salesRepo, private ProductRepository $productRepo)
    {
    }

    public function createSale(array $data): int
    {
        // check stock
        $produtoId = (int)$data['produto_id'];
        $quantidade = (float)$data['quantidade'];
        $prod = $this->productRepo->findById($produtoId);
        $estoque = $prod ? (float)$prod['estoque_atual'] : 0.0;
        if ($quantidade > $estoque) {
            throw new \RuntimeException('Estoque insuficiente');
        }

        // calculate financials
        $receitaTotal = $quantidade * (float)$data['valor_unitario'];
        $custoProporcional = $quantidade * (float)$prod['custo_medio'];
        $lucroBruto = $receitaTotal - $custoProporcional;
        $margem = $receitaTotal > 0 ? ($lucroBruto / $receitaTotal) * 100.0 : 0.0;

        $payload = array_merge($data, [
            'receita_total' => $receitaTotal,
            'custo_proporcional' => $custoProporcional,
            'lucro_bruto' => $lucroBruto,
            'margem_percentual' => $margem,
            'status' => $data['status'] ?? 'ORCAMENTO'
        ]);

        $id = $this->salesRepo->create($payload);
        Logger::get()->info('Venda criada', ['id' => $id, 'payload' => $payload]);
        return $id;
    }

    public function confirmDelivery(int $saleId): array
    {
        $sale = $this->salesRepo->findById($saleId);
        if (!$sale) throw new \RuntimeException('Venda não encontrada');
        if ($sale['status'] === 'ENTREGUE') throw new \RuntimeException('Venda já entregue');

        // decrease stock
        $produtoId = (int)$sale['produto_id'];
        $quantidade = (float)$sale['quantidade'];
        $prod = $this->productRepo->findById($produtoId);
        $estoque = $prod ? (float)$prod['estoque_atual'] : 0.0;
        if ($quantidade > $estoque) throw new \RuntimeException('Estoque insuficiente para entrega');

        $novoEstoque = $estoque - $quantidade;
        $this->productRepo->updateStockAndCost($produtoId, $novoEstoque, (float)$prod['custo_medio']);
        $this->salesRepo->updateStatus($saleId, 'ENTREGUE');

        Logger::get()->info('Venda entregue', ['sale_id' => $saleId]);

        return ['message' => 'ENTREGUE', 'novo_estoque' => $novoEstoque];
    }
}
