<?php
namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Repositories\ProductRepository;
use App\Logger\Logger;

class PurchaseService
{
    public function __construct(private PurchaseRepository $purchaseRepo, private ProductRepository $productRepo)
    {
    }

    public function createPurchase(array $data): int
    {
        $id = $this->purchaseRepo->create($data);
        Logger::get()->info('Compra criada', ['id' => $id, 'data' => $data]);
        return $id;
    }

    public function receivePurchase(int $id): array
    {
        $compra = $this->purchaseRepo->findById($id);
        if (!$compra) {
            throw new \RuntimeException('Compra não encontrada');
        }

        if ($compra['status'] === 'RECEBIDA') {
            return ['message' => 'Compra já recebida'];
        }

        $this->purchaseRepo->updateStatus($id, 'RECEBIDA');

        $produtoId = (int)$compra['produto_id'];
        $quantidade = (float)$compra['quantidade'];
        $valorUnitario = (float)$compra['valor_unitario'];
        $comissaoTotal = (float)$compra['comissao_total'];

        $prod = $this->productRepo->findById($produtoId);
        $oldQty = $prod ? (float)$prod['estoque_atual'] : 0.0;
        $oldCost = $prod ? (float)$prod['custo_medio'] : 0.0;

        $valorTotalCompra = ($quantidade * $valorUnitario) + $comissaoTotal;

        $newQty = $oldQty + $quantidade;
        $newCost = 0.0;
        if ($newQty > 0) {
            $newCost = (($oldQty * $oldCost) + $valorTotalCompra) / $newQty;
        }

        $this->productRepo->updateStockAndCost($produtoId, $newQty, $newCost);

        Logger::get()->info('Compra recebida e estoque atualizado', ['compra_id' => $id, 'produto_id' => $produtoId, 'novo_estoque' => $newQty]);

        return ['message' => 'RECEBIDA', 'produto_id' => $produtoId, 'novo_estoque' => $newQty, 'novo_custo_medio' => $newCost];
    }
}
