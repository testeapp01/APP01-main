<?php
namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Repositories\ProductRepository;
use App\Logger\Logger;

class PurchaseService
{
    public function __construct(private PurchaseRepository $purchaseRepo, private ?ProductRepository $productRepo = null)
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

        $novoEstoque = null;
        $novoCustoMedio = null;
        if ($this->productRepo !== null) {
            $produtoId = (int)($compra['produto_id'] ?? 0);
            $quantidade = (float)($compra['quantidade'] ?? 0);
            $valorUnitario = (float)($compra['valor_unitario'] ?? 0);
            if ($produtoId > 0 && $quantidade > 0) {
                $this->productRepo->updateStockOnReceive($produtoId, $quantidade, $valorUnitario);
                $produtoAtualizado = $this->productRepo->findById($produtoId);
                $novoEstoque = $produtoAtualizado ? (float)($produtoAtualizado['estoque_atual'] ?? 0) : null;
                $novoCustoMedio = $produtoAtualizado ? (float)($produtoAtualizado['custo_medio'] ?? 0) : null;
            }
        }

        Logger::get()->info('Compra recebida', ['compra_id' => $id]);

        return ['message' => 'RECEBIDA', 'novo_estoque' => $novoEstoque, 'novo_custo_medio' => $novoCustoMedio];
    }
}
