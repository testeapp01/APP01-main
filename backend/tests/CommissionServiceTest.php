<?php
use PHPUnit\Framework\TestCase;
use App\Services\CommissionService;

final class CommissionServiceTest extends TestCase
{
    public function testCalculatePercentualPlusExtra(): void
    {
        $res = CommissionService::calculate(100.0, 5.0, 'percentual', 2.0, 0.5);
        $this->assertEquals(500.0, $res['valor_total']);
        $this->assertEquals(10.0, $res['comissao']); // 2% of 500
        $this->assertEquals(50.0, $res['extra_total']); // 100 * 0.5
        $this->assertEquals(60.0, $res['comissao_total']);
        $this->assertEquals(560.0, $res['custo_final_real']);
    }

    public function testCalculateFixaNoExtra(): void
    {
        $res = CommissionService::calculate(10, 2, 'fixa', 15.0, 0);
        $this->assertEquals(20.0, $res['valor_total']);
        $this->assertEquals(15.0, $res['comissao']);
        $this->assertEquals(0.0, $res['extra_total']);
        $this->assertEquals(15.0, $res['comissao_total']);
        $this->assertEquals(35.0, $res['custo_final_real']);
    }
}
