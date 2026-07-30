<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import;

use App\Domain\Fuelings\Data\FuelingImportRowData;
use App\Domain\Fuelings\Data\FuelingStationImportData;
use App\Domain\Fuelings\Enums\FuelingPaymentMethod;
use App\Domain\Fuelings\Enums\FuelProduct;
use App\Domain\Fuelings\Enums\FuelTank;
use App\Domain\Fuelings\Import\Exceptions\FuelingImportRowException;
use App\Support\Cnpj\Cnpj;
use App\Support\Cpf\Cpf;
use App\Support\Money\Brl;
use DateTimeImmutable;

/**
 * Valida e normaliza uma linha crua da planilha. Puro: nenhuma consulta ao
 * banco, o que deixa cada regra de formato testável isoladamente.
 *
 * A data é lida como relógio de parede e gravada como veio, sem conversão de
 * fuso — igual ao que o formulário da tela faz. Converter aqui deixaria o
 * abastecimento importado 3 horas fora do que foi lançado à mão.
 */
final class FuelingRowParser
{
    /**
     * Formatos aceitos na coluna de data. O leitor já entrega célula formatada
     * como data em `Y-m-d H:i:s`; os demais são o que se digita à mão.
     *
     * @var list<string>
     */
    private const DATE_FORMATS = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
        'Y-m-d',
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd/m/Y',
        'j/n/Y H:i:s',
        'j/n/Y H:i',
        'j/n/Y',
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd-m-Y',
    ];

    public function parse(FuelingImportRow $row): FuelingImportRowData
    {
        $liters = $this->decimal($row, FuelingImportSheet::LITERS, true);

        if ($liters === null || $liters <= 0.0 || $liters > 99999.0) {
            throw FuelingImportRowException::outOfRange(
                FuelingImportSheet::LITERS,
                (string) $row->text(FuelingImportSheet::LITERS),
                'informe uma quantidade de litros maior que zero',
            );
        }

        $pricePerLiter = $this->decimal($row, FuelingImportSheet::PRICE_PER_LITER, false);

        if ($pricePerLiter !== null && ($pricePerLiter < 0.0 || $pricePerLiter > 999.0)) {
            throw FuelingImportRowException::outOfRange(
                FuelingImportSheet::PRICE_PER_LITER,
                (string) $row->text(FuelingImportSheet::PRICE_PER_LITER),
                'o preço por litro tem que ficar entre 0 e 999',
            );
        }

        return new FuelingImportRowData(
            number: $row->number,
            plate: $this->plate($row),
            fueledAt: $this->fueledAt($row),
            odometer: $this->odometer($row),
            product: $this->product($row),
            liters: $liters,
            totalCents: $this->totalCents($row),
            paymentMethod: $this->paymentMethod($row),
            tank: $this->tank($row),
            fullTank: $this->fullTank($row),
            station: $this->station($row),
            importCode: $this->text($row, FuelingImportSheet::CODE, 60),
            pricePerLiter: $pricePerLiter,
            driverCpf: $this->driverCpf($row),
            invoiceNumber: $this->text($row, FuelingImportSheet::INVOICE_NUMBER, 60),
            notes: $this->text($row, FuelingImportSheet::NOTES, 2000),
        );
    }

    private function plate(FuelingImportRow $row): string
    {
        $raw = $row->text(FuelingImportSheet::PLATE);

        if ($raw === null) {
            throw FuelingImportRowException::missing(FuelingImportSheet::PLATE);
        }

        $plate = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($raw)) ?? '';

        if (strlen($plate) < 7 || strlen($plate) > 8) {
            throw FuelingImportRowException::invalidPlate($raw);
        }

        return $plate;
    }

    private function fueledAt(FuelingImportRow $row): string
    {
        $raw = $row->text(FuelingImportSheet::FUELED_AT);

        if ($raw === null) {
            throw FuelingImportRowException::missing(FuelingImportSheet::FUELED_AT);
        }

        foreach (self::DATE_FORMATS as $format) {
            // O "!" zera hora e minuto não informados em vez de herdar o agora.
            $date = DateTimeImmutable::createFromFormat('!'.$format, $raw);

            if ($date instanceof DateTimeImmutable && DateTimeImmutable::getLastErrors() === false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        throw FuelingImportRowException::invalidDate($raw);
    }

    private function odometer(FuelingImportRow $row): int
    {
        $raw = $row->text(FuelingImportSheet::ODOMETER);

        if ($raw === null) {
            throw FuelingImportRowException::missing(FuelingImportSheet::ODOMETER);
        }

        // O odômetro é inteiro: sem vírgula no texto, um ponto só pode ser
        // separador de milhar ("158.420"), nunca casa decimal.
        $normalized = str_contains($raw, ',')
            ? Brl::normalizeDecimal($raw)
            : Brl::normalizeDecimal(str_replace('.', '', $raw));

        if ($normalized === null || ! is_numeric($normalized)) {
            throw FuelingImportRowException::invalidNumber(FuelingImportSheet::ODOMETER, $raw);
        }

        $odometer = (int) round((float) $normalized);

        if ($odometer < 0 || $odometer > 9999999) {
            throw FuelingImportRowException::outOfRange(
                FuelingImportSheet::ODOMETER,
                $raw,
                'o odômetro tem que ficar entre 0 e 9.999.999 km',
            );
        }

        return $odometer;
    }

    private function totalCents(FuelingImportRow $row): int
    {
        $raw = $row->text(FuelingImportSheet::TOTAL);

        if ($raw === null) {
            throw FuelingImportRowException::missing(FuelingImportSheet::TOTAL);
        }

        $cents = Brl::toCents($raw);

        if ($cents === null) {
            throw FuelingImportRowException::invalidNumber(FuelingImportSheet::TOTAL, $raw);
        }

        if ($cents < 1) {
            throw FuelingImportRowException::outOfRange(FuelingImportSheet::TOTAL, $raw, 'informe o valor total pago');
        }

        return $cents;
    }

    private function product(FuelingImportRow $row): FuelProduct
    {
        $raw = $row->text(FuelingImportSheet::PRODUCT);

        if ($raw === null) {
            throw FuelingImportRowException::missing(FuelingImportSheet::PRODUCT);
        }

        foreach (FuelProduct::cases() as $case) {
            if ($this->matches($raw, $case->value, $case->label())) {
                return $case;
            }
        }

        throw FuelingImportRowException::invalidOption(
            FuelingImportSheet::PRODUCT,
            $raw,
            array_map(static fn (FuelProduct $case): string => $case->value, FuelProduct::cases()),
        );
    }

    private function tank(FuelingImportRow $row): FuelTank
    {
        $raw = $row->text(FuelingImportSheet::TANK);

        if ($raw === null) {
            return FuelTank::Main;
        }

        foreach (FuelTank::cases() as $case) {
            if ($this->matches($raw, $case->value, $case->label())) {
                return $case;
            }
        }

        throw FuelingImportRowException::invalidOption(
            FuelingImportSheet::TANK,
            $raw,
            array_map(static fn (FuelTank $case): string => $case->value, FuelTank::cases()),
        );
    }

    private function paymentMethod(FuelingImportRow $row): FuelingPaymentMethod
    {
        $raw = $row->text(FuelingImportSheet::PAYMENT_METHOD);

        if ($raw === null) {
            throw FuelingImportRowException::missing(FuelingImportSheet::PAYMENT_METHOD);
        }

        foreach (FuelingPaymentMethod::cases() as $case) {
            if ($this->matches($raw, $case->value, $case->label())) {
                return $case;
            }
        }

        throw FuelingImportRowException::invalidOption(
            FuelingImportSheet::PAYMENT_METHOD,
            $raw,
            array_map(static fn (FuelingPaymentMethod $case): string => $case->value, FuelingPaymentMethod::cases()),
        );
    }

    private function fullTank(FuelingImportRow $row): bool
    {
        $raw = $row->text(FuelingImportSheet::FULL_TANK);

        if ($raw === null) {
            return false;
        }

        $slug = FuelingImportSheet::slug($raw);

        return match ($slug) {
            '1', 'sim', 's', 'x', 'true', 'verdadeiro', 'yes', 'y' => true,
            '0', 'nao', 'n', 'false', 'falso', 'no' => false,
            default => throw FuelingImportRowException::invalidOption(
                FuelingImportSheet::FULL_TANK,
                $raw,
                ['sim', 'não'],
            ),
        };
    }

    private function driverCpf(FuelingImportRow $row): ?string
    {
        $raw = $row->text(FuelingImportSheet::DRIVER_CPF);

        if ($raw === null) {
            return null;
        }

        $digits = Cpf::digits($raw);

        if (! Cpf::isValid($digits)) {
            throw FuelingImportRowException::invalidCpf($raw);
        }

        return $digits;
    }

    private function station(FuelingImportRow $row): FuelingStationImportData
    {
        $rawDocument = $row->text(FuelingImportSheet::STATION_DOCUMENT);
        $document = null;

        if ($rawDocument !== null) {
            $document = Cnpj::digits($rawDocument);

            if (! Cnpj::isValid($document)) {
                throw FuelingImportRowException::invalidCnpj($rawDocument);
            }
        }

        return new FuelingStationImportData(
            document: $document,
            legalName: $this->text($row, FuelingImportSheet::STATION_LEGAL_NAME, 150),
            tradeName: $this->text($row, FuelingImportSheet::STATION_TRADE_NAME, 150),
            city: $this->text($row, FuelingImportSheet::STATION_CITY, 80),
            state: $this->state($row),
        );
    }

    private function state(FuelingImportRow $row): ?string
    {
        $raw = $row->text(FuelingImportSheet::STATION_STATE);

        if ($raw === null) {
            return null;
        }

        $state = mb_strtoupper(trim($raw));

        if (preg_match('/^[A-Z]{2}$/', $state) !== 1) {
            throw FuelingImportRowException::invalidState($raw);
        }

        return $state;
    }

    private function text(FuelingImportRow $row, string $column, int $max): ?string
    {
        $value = $row->text($column);

        if ($value === null) {
            return null;
        }

        if (mb_strlen($value) > $max) {
            throw FuelingImportRowException::tooLong($column, $max);
        }

        return $value;
    }

    private function decimal(FuelingImportRow $row, string $column, bool $required): ?float
    {
        $raw = $row->text($column);

        if ($raw === null) {
            if ($required) {
                throw FuelingImportRowException::missing($column);
            }

            return null;
        }

        $normalized = Brl::normalizeDecimal($raw);

        if ($normalized === null || ! is_numeric($normalized)) {
            throw FuelingImportRowException::invalidNumber($column, $raw);
        }

        return (float) $normalized;
    }

    /**
     * Aceita o valor técnico do enum ou o rótulo pt-BR. Os separadores são
     * descartados na comparação para "Arla 32" casar com `arla32`.
     */
    private function matches(string $raw, string $value, string $label): bool
    {
        $candidate = str_replace('_', '', FuelingImportSheet::slug($raw));

        return $candidate === str_replace('_', '', $value)
            || $candidate === str_replace('_', '', FuelingImportSheet::slug($label));
    }
}
