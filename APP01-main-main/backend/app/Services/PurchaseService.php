<?php
namespace App\Services;

use App\Repositories\PurchaseRepository;
use App\Logger\Logger;

class PurchaseService
{
    public function __construct(private PurchaseRepository $purchaseRepo)
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

        Logger::get()->info('Compra recebida', ['compra_id' => $id]);

        return ['message' => 'RECEBIDA'];
    }
}
