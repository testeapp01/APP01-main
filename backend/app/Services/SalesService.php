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
        $produtoId = (int)($data['produto_id'] ?? 0);
        if ($produtoId <= 0) {
            throw new \RuntimeException('produto_id inválido', 400);
        }

        $quantidade = (float)($data['quantidade'] ?? 0);
        if ($quantidade <= 0) {
            throw new \RuntimeException('Quantidade deve ser maior que zero', 400);
        }

        $valorUnitario = (float)($data['valor_unitario'] ?? 0);
        if ($valorUnitario <= 0) {
            throw new \RuntimeException('Valor unitário deve ser maior que zero', 400);
        }

        $prod = $this->productRepo->findById($produtoId);
        if (!$prod) {
            throw new \RuntimeException('Produto não encontrado', 404);
        }

        // calculate financials
        $receitaTotal = $quantidade * $valorUnitario;
        $custoProporcional = $quantidade * (float)$prod['custo_medio'];
        $lucroBruto = $receitaTotal - $custoProporcional;
        $margem = $receitaTotal > 0 ? ($lucroBruto / $receitaTotal) * 100.0 : 0.0;

        $payload = array_merge($data, [
            'receita_total' => $receitaTotal,
            'custo_proporcional' => $custoProporcional,
            'lucro_bruto' => $lucroBruto,
            'margem_percentual' => $margem,
            'status' => $data['status'] ?? 'AGUARDANDO'
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
        $this->salesRepo->updateStatus($saleId, 'ENTREGUE');

        Logger::get()->info('Venda entregue', ['sale_id' => $saleId]);

        return ['message' => 'ENTREGUE'];
    }
}
