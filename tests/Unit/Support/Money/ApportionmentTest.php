<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Money;

use App\Support\Money\Apportionment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ApportionmentTest extends TestCase
{
    public function test_rateio_divide_mil_reais_em_tres_partes_com_maior_resto(): void
    {
        $parts = Apportionment::equally(100_000, 3);

        $this->assertSame([33_333, 33_333, 33_334], $parts);
        $this->assertSame(100_000, array_sum($parts));
    }

    public function test_rateio_divide_mil_reais_em_sete_partes_com_soma_exata(): void
    {
        $parts = Apportionment::equally(100_000, 7);

        $this->assertSame(7, count($parts));
        $this->assertSame(100_000, array_sum($parts));
        $this->assertSame([14_285, 14_285, 14_286, 14_286, 14_286, 14_286, 14_286], $parts);
    }

    public function test_rateio_divide_em_uma_parte_retorna_total_integral(): void
    {
        $parts = Apportionment::equally(100_000, 1);

        $this->assertSame([100_000], $parts);
    }

    public function test_rateio_com_zero_partes_retorna_lista_vazia(): void
    {
        $parts = Apportionment::equally(100_000, 0);

        $this->assertSame([], $parts);
    }

    public function test_rateio_ponderado_mantem_soma_exata(): void
    {
        $parts = Apportionment::distribute(1_000, [10, 20, 30]);

        $this->assertSame([167, 333, 500], $parts);
        $this->assertSame(1_000, array_sum($parts));
    }

    public function test_rateio_com_peso_negativo_dispara_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Apportionment::distribute(1_000, [10, -2, 4]);
    }
}
