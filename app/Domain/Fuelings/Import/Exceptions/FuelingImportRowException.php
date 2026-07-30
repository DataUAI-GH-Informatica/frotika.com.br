<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import\Exceptions;

use App\Support\Cpf\Cpf;
use App\Support\Format;
use RuntimeException;

/**
 * Problema de UMA linha da planilha. Nunca derruba o lote: vira uma linha com
 * situação "Falhou" e esta mensagem na tela de resultado. Toda mensagem tem que
 * dizer o que fazer, porque quem lê vai voltar para a planilha e corrigir.
 */
final class FuelingImportRowException extends RuntimeException
{
    public static function missing(string $column): self
    {
        return new self(sprintf('Preencha a coluna "%s".', $column));
    }

    public static function invalidNumber(string $column, string $value): self
    {
        return new self(sprintf('Valor "%s" inválido em "%s". Use apenas números, com vírgula na casa decimal.', $value, $column));
    }

    public static function outOfRange(string $column, string $value, string $expectation): self
    {
        return new self(sprintf('Valor "%s" inválido em "%s": %s.', $value, $column, $expectation));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function invalidOption(string $column, string $value, array $allowed): self
    {
        return new self(sprintf('Valor "%s" inválido em "%s". Use um destes: %s.', $value, $column, implode(', ', $allowed)));
    }

    public static function invalidDate(string $value): self
    {
        return new self(sprintf('Data "%s" inválida. Use dd/mm/aaaa hh:mm — por exemplo 01/07/2026 08:30.', $value));
    }

    public static function invalidPlate(string $value): self
    {
        return new self(sprintf('Placa "%s" inválida. Use o formato ABC1D23 ou ABC1234.', $value));
    }

    public static function vehicleNotFound(string $plate): self
    {
        return new self(sprintf('Nenhum veículo com a placa %s nesta empresa. Cadastre o veículo antes de importar.', Format::plate($plate)));
    }

    public static function invalidCpf(string $value): self
    {
        return new self(sprintf('CPF "%s" inválido na coluna "cpf_motorista".', $value));
    }

    public static function driverNotFound(string $cpf): self
    {
        return new self(sprintf('Nenhum motorista com o CPF %s nesta empresa. Cadastre o motorista ou deixe a coluna em branco.', Cpf::format($cpf)));
    }

    public static function invalidCnpj(string $value): self
    {
        return new self(sprintf('CNPJ "%s" inválido na coluna "posto_cnpj".', $value));
    }

    public static function invalidState(string $value): self
    {
        return new self(sprintf('UF "%s" inválida na coluna "posto_uf". Use a sigla de 2 letras — por exemplo MG.', $value));
    }

    public static function tooLong(string $column, int $max): self
    {
        return new self(sprintf('O texto da coluna "%s" passa de %d caracteres.', $column, $max));
    }

    /**
     * A tela tem um "confirmar correção" para odômetro regressivo; a planilha
     * não, de propósito — em importação em lote, aceitar km para trás em
     * silêncio estraga o km/l de todo o histórico do veículo.
     */
    public static function odometerRollback(string $plate, int $odometer): self
    {
        return new self(sprintf(
            'Odômetro %s é menor que o último lançado para %s. Corrija a planilha ou lance esta correção pela tela.',
            Format::km($odometer),
            Format::plate($plate),
        ));
    }

    public static function rejected(string $message): self
    {
        return new self($message);
    }
}
