<?php
namespace App\Services;

class CommissionService
{
    /**
     * Calculate commission and costs according to rules.
     *
     * @param float $quantity
     * @param float $unitPrice
     * @param string $type 'percentual'|'fixa'|null
     * @param float|null $value percent (e.g. 2.5) or fixed amount
     * @param float|null $extraPerSack
     * @return array
     */
    public static function calculate(float $quantity, float $unitPrice, ?string $type = null, ?float $value = null, ?float $extraPerSack = null): array
    {
        $valorTotal = $quantity * $unitPrice;

        $comissao = 0.0;
        if ($type === 'percentual' && $value !== null) {
            $comissao = $valorTotal * ($value / 100.0);
        } elseif ($type === 'fixa' && $value !== null) {
            $comissao = $value;
        }

        $extraTotal = 0.0;
        if ($extraPerSack !== null && $extraPerSack > 0) {
            $extraTotal = $quantity * $extraPerSack;
        }

        $comissaoTotal = $comissao + $extraTotal;

        $custoFinalReal = $valorTotal + $comissaoTotal;

        return [
            'valor_total' => $valorTotal,
            'comissao' => $comissao,
            'extra_total' => $extraTotal,
            'comissao_total' => $comissaoTotal,
            'custo_final_real' => $custoFinalReal,
        ];
    }
}
