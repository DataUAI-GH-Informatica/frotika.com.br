<?php

declare(strict_types=1);

namespace Tests\Unit\Casts;

use App\Casts\Money as MoneyCast;
use App\Models\User;
use App\Support\Money\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyCastTest extends TestCase
{
    public function test_cast_get_converte_centavos_para_value_object(): void
    {
        $cast = new MoneyCast;

        $value = $cast->get(new User, 'amount_cents', 12345, []);

        $this->assertInstanceOf(Money::class, $value);
        $this->assertSame(12_345, $value?->cents());
    }

    public function test_cast_set_aceita_value_object(): void
    {
        $cast = new MoneyCast;

        $stored = $cast->set(new User, 'amount_cents', Money::fromCents(9_990), []);

        $this->assertSame(9_990, $stored);
    }

    public function test_cast_set_aceita_int_ou_string_numerica(): void
    {
        $cast = new MoneyCast;

        $this->assertSame(1500, $cast->set(new User, 'amount_cents', 1500, []));
        $this->assertSame(2500, $cast->set(new User, 'amount_cents', '2500', []));
    }

    public function test_cast_set_com_valor_invalido_dispara_excecao(): void
    {
        $cast = new MoneyCast;

        $this->expectException(InvalidArgumentException::class);

        $cast->set(new User, 'amount_cents', 15.5, []);
    }
}
