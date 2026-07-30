<?php

declare(strict_types=1);

namespace App\Support\Money;

/**
 * Conversao de entrada em reais (formulario pt-BR) para centavos inteiros.
 * Dinheiro nunca vira float na base (regra 1); esta e a unica porta de entrada
 * de um valor digitado para o inteiro em centavos que o dominio espera.
 */
final class Brl
{
    public static function toCents(int|string|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $normalized = self::normalizeDecimal($value);

        if ($normalized === null) {
            return null;
        }

        return (int) round(((float) $normalized) * 100);
    }

    /**
     * Converte um decimal digitado em pt-BR para a notacao que o PHP entende,
     * sem virar float no caminho. Serve tanto para dinheiro quanto para litros e
     * preco por litro, que nao sao centavos mas chegam com a mesma pontuacao.
     */
    public static function normalizeDecimal(int|float|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
        }

        $raw = trim($value);

        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', $raw) ?? '';

        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            // pt-BR: ponto e milhar, virgula e decimal.
            return str_replace(['.', ','], ['', '.'], $normalized);
        }

        if ($hasComma) {
            return str_replace(',', '.', $normalized);
        }

        return $normalized;
    }
}
