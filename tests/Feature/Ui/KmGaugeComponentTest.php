<?php

declare(strict_types=1);

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class KmGaugeComponentTest extends TestCase
{
    public function test_renderiza_regua_com_valores_formatados_via_format(): void
    {
        $html = Blade::render('<x-ui.km-gauge :revenue="4.37" :cost="3.95" :breakeven="3.78" />');

        // Confirma que o alias global Format resolve dentro da Blade.
        $this->assertStringContainsString('4,37', $html);
        $this->assertStringContainsString('3,95', $html);
        $this->assertStringContainsString('3,78', $html);

        // Margem positiva: 4,37 - 3,95 = 0,42, com sinal de mais.
        $this->assertStringContainsString('+', $html);
        $this->assertStringContainsString('0,42', $html);
        $this->assertStringContainsString('Equilíbrio', $html);
    }

    public function test_regua_invertida_marca_resultado_negativo(): void
    {
        $html = Blade::render('<x-ui.km-gauge :revenue="3.12" :cost="3.44" :breakeven="3.30" />');

        // Margem negativa: 3,12 - 3,44 = -0,32, com sinal de menos tipográfico.
        $this->assertStringContainsString('−', $html);
        $this->assertStringContainsString('0,32', $html);
        $this->assertStringContainsString('negativa', $html);
    }
}
