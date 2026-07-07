<?php
namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Repositories\ProductRepository;
use App\Logger\Logger;

class PurchaseService
{
    public function __construct(
        private PurchaseRepository $purchaseRepo,
        private ProductRepository $productRepo
    ) {
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

        $produtoId = (int)($compra['produto_id'] ?? 0);
        $produto = $produtoId > 0 ? $this->productRepo->findById($produtoId) : null;
        if (!$produto) {
            throw new \RuntimeException('Produto não encontrado', 404);
        }

        $quantidadeRecebida = (float)($compra['quantidade'] ?? 0);
        $estoqueAtual = (float)($produto['estoque_atual'] ?? 0);
        $custoMedioAtual = (float)($produto['custo_medio'] ?? 0);

        // Custo total desta compra: usa custo_final_real (com comissão/extra) quando
        // disponível; cai para custo_total quando não houver esse detalhamento.
        $custoTotalCompra = isset($compra['custo_final_real']) && $compra['custo_final_real'] !== null
            ? (float)$compra['custo_final_real']
            : (float)($compra['custo_total'] ?? 0);

        $novoEstoque = $estoqueAtual + $quantidadeRecebida;

        // Média ponderada pelo custo já investido no estoque atual + custo desta compra.
        $novoCustoMedio = $novoEstoque > 0
            ? (($estoqueAtual * $custoMedioAtual) + $custoTotalCompra) / $novoEstoque
            : $custoMedioAtual;

        $this->productRepo->updateStock($produtoId, $novoEstoque, $novoCustoMedio);
        $this->purchaseRepo->updateStatus($id, 'RECEBIDA');

        Logger::get()->info('Compra recebida', [
            'compra_id' => $id,
            'novo_estoque' => $novoEstoque,
            'novo_custo_medio' => $novoCustoMedio,
        ]);

        return [
            'message' => 'RECEBIDA',
            'novo_estoque' => $novoEstoque,
            'novo_custo_medio' => $novoCustoMedio,
        ];
    }
}
