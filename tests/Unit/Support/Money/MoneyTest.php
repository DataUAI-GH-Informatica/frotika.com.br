<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Money;

use App\Support\Money\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_soma_dinheiro_em_mesma_moeda(): void
    {
        $total = Money::fromCents(10_000)->add(Money::fromCents(2_500));

        $this->assertSame(12_500, $total->cents());
        $this->assertSame('BRL', $total->currency());
    }

    public function test_subtracao_dinheiro_em_mesma_moeda(): void
    {
        $total = Money::fromCents(10_000)->subtract(Money::fromCents(3_250));

        $this->assertSame(6_750, $total->cents());
    }

    public function test_operacoes_com_moedas_diferentes_disparam_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(1_000, 'BRL')->add(Money::fromCents(2_000, 'USD'));
    }

    public function test_rateio_igual_retorna_money_com_soma_exata(): void
    {
        $parts = Money::fromCents(100_000)->apportionEqually(3);

        $this->assertCount(3, $parts);
        $this->assertSame([33_333, 33_333, 33_334], array_map(fn (Money $part): int => $part->cents(), $parts));
        $this->assertSame(100_000, array_sum(array_map(fn (Money $part): int => $part->cents(), $parts)));
    }

    public function test_rateio_ponderado_retorna_money_com_soma_exata(): void
    {
        $parts = Money::fromCents(1_000)->apportionByWeights([10, 20, 30]);

        $this->assertSame([167, 333, 500], array_map(fn (Money $part): int => $part->cents(), $parts));
    }
}
