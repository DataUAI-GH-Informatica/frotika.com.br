<?php

declare(strict_types=1);

namespace Tests\Unit\Fuelings;

use App\Domain\Fuelings\Data\FuelingImportRowData;
use App\Domain\Fuelings\Enums\FuelingPaymentMethod;
use App\Domain\Fuelings\Enums\FuelProduct;
use App\Domain\Fuelings\Enums\FuelTank;
use App\Domain\Fuelings\Import\Exceptions\FuelingImportRowException;
use App\Domain\Fuelings\Import\FuelingImportRow;
use App\Domain\Fuelings\Import\FuelingImportSheet;
use App\Domain\Fuelings\Import\FuelingRowParser;
use PHPUnit\Framework\TestCase;

final class FuelingRowParserTest extends TestCase
{
    public function test_converte_valores_ptbr_para_os_tipos_do_dominio(): void
    {
        $data = $this->parse([
            FuelingImportSheet::CODE => 'AB-1001',
            FuelingImportSheet::PLATE => 'rta-4b56',
            FuelingImportSheet::FUELED_AT => '01/07/2026 08:30',
            FuelingImportSheet::ODOMETER => '158.420',
            FuelingImportSheet::LITERS => '180,500',
            FuelingImportSheet::PRICE_PER_LITER => '5,899',
            FuelingImportSheet::TOTAL => '1.064,77',
            FuelingImportSheet::TANK => 'auxiliary',
            FuelingImportSheet::FULL_TANK => 'sim',
            FuelingImportSheet::DRIVER_CPF => '529.982.247-25',
            FuelingImportSheet::STATION_DOCUMENT => '11.222.333/0001-81',
            FuelingImportSheet::STATION_STATE => 'mg',
        ]);

        $this->assertSame('RTA4B56', $data->plate);
        $this->assertSame('2026-07-01 08:30:00', $data->fueledAt);
        // Ponto no odômetro é separador de milhar: 158.420 km, não 158 km.
        $this->assertSame(158420, $data->odometer);
        $this->assertSame(180.5, $data->liters);
        $this->assertSame(5.899, $data->pricePerLiter);
        $this->assertSame(106477, $data->totalCents);
        $this->assertSame(FuelTank::Auxiliary, $data->tank);
        $this->assertTrue($data->fullTank);
        $this->assertSame('52998224725', $data->driverCpf);
        $this->assertSame('11222333000181', $data->station->documentDigits());
        $this->assertSame('MG', $data->station->state);
        $this->assertSame('AB-1001', $data->importCode);
    }

    public function test_usa_padroes_quando_as_colunas_opcionais_estao_vazias(): void
    {
        $data = $this->parse();

        $this->assertSame(FuelTank::Main, $data->tank);
        $this->assertFalse($data->fullTank);
        $this->assertNull($data->pricePerLiter);
        $this->assertNull($data->importCode);
        $this->assertNull($data->driverCpf);
        $this->assertTrue($data->station->isEmpty());
    }

    public function test_aceita_data_ja_normalizada_pelo_leitor_e_dia_sem_zero(): void
    {
        $this->assertSame(
            '2026-07-01 08:30:00',
            $this->parse([FuelingImportSheet::FUELED_AT => '2026-07-01 08:30:00'])->fueledAt,
        );

        $this->assertSame(
            '2026-07-01 00:00:00',
            $this->parse([FuelingImportSheet::FUELED_AT => '1/7/2026'])->fueledAt,
        );
    }

    public function test_aceita_rotulo_ptbr_no_lugar_do_valor_do_enum(): void
    {
        $data = $this->parse([
            FuelingImportSheet::PRODUCT => 'Arla 32',
            FuelingImportSheet::PAYMENT_METHOD => 'Cartão de abastecimento',
            FuelingImportSheet::TANK => 'Principal',
        ]);

        $this->assertSame(FuelProduct::Arla32, $data->product);
        $this->assertSame(FuelingPaymentMethod::FuelCard, $data->paymentMethod);
        $this->assertSame(FuelTank::Main, $data->tank);
    }

    public function test_recusa_data_invalida(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('Data "31/02/2026" inválida.');

        $this->parse([FuelingImportSheet::FUELED_AT => '31/02/2026']);
    }

    public function test_recusa_placa_fora_do_formato(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('Placa "ABC" inválida.');

        $this->parse([FuelingImportSheet::PLATE => 'ABC']);
    }

    public function test_recusa_produto_desconhecido(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('diesel_s10');

        $this->parse([FuelingImportSheet::PRODUCT => 'querosene']);
    }

    public function test_recusa_litros_zerados(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('maior que zero');

        $this->parse([FuelingImportSheet::LITERS => '0']);
    }

    public function test_recusa_total_sem_valor(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('valor_total');

        $this->parse([FuelingImportSheet::TOTAL => '0,00']);
    }

    public function test_recusa_coluna_obrigatoria_em_branco(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('Preencha a coluna "forma_pagamento".');

        $this->parse([FuelingImportSheet::PAYMENT_METHOD => null]);
    }

    public function test_recusa_cpf_e_cnpj_invalidos(): void
    {
        try {
            $this->parse([FuelingImportSheet::DRIVER_CPF => '111.111.111-11']);
            $this->fail('CPF inválido deveria ter falhado.');
        } catch (FuelingImportRowException $exception) {
            $this->assertStringContainsString('cpf_motorista', $exception->getMessage());
        }

        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('posto_cnpj');

        $this->parse([FuelingImportSheet::STATION_DOCUMENT => '11.222.333/0001-99']);
    }

    public function test_recusa_uf_com_mais_de_duas_letras(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('UF "Minas" inválida');

        $this->parse([FuelingImportSheet::STATION_STATE => 'Minas']);
    }

    public function test_recusa_tanque_cheio_que_nao_e_sim_nem_nao(): void
    {
        $this->expectException(FuelingImportRowException::class);
        $this->expectExceptionMessage('tanque_cheio');

        $this->parse([FuelingImportSheet::FULL_TANK => 'talvez']);
    }

    /**
     * @param  array<string, string|null>  $overrides
     */
    private function parse(array $overrides = []): FuelingImportRowData
    {
        $cells = array_merge([
            FuelingImportSheet::PLATE => 'RTA4B56',
            FuelingImportSheet::FUELED_AT => '01/07/2026 08:30',
            FuelingImportSheet::ODOMETER => '158420',
            FuelingImportSheet::PRODUCT => 'diesel_s10',
            FuelingImportSheet::LITERS => '180,500',
            FuelingImportSheet::TOTAL => '1.064,77',
            FuelingImportSheet::PAYMENT_METHOD => 'cash',
        ], $overrides);

        return (new FuelingRowParser)->parse(new FuelingImportRow(2, $cells));
    }
}
