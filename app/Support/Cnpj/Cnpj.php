<?php

declare(strict_types=1);

namespace App\Support\Cnpj;

/**
 * Utilitário de CNPJ: normalização, validação de dígitos verificadores e
 * formatação pt-BR. Fonte única para não duplicar a regra do checksum.
 */
final class Cnpj
{
    /**
     * Mantém apenas os dígitos.
     */
    public static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Formata no padrão 00.000.000/0000-00 a partir de qualquer entrada.
     */
    public static function format(string $value): string
    {
        $digits = self::digits($value);

        if (strlen($digits) !== 14) {
            return $digits;
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 3),
            substr($digits, 5, 3),
            substr($digits, 8, 4),
            substr($digits, 12, 2),
        );
    }

    /**
     * Valida os 14 dígitos e os dois dígitos verificadores.
     */
    public static function isValid(string $value): bool
    {
        $cnpj = self::digits($value);

        if (preg_match('/^\d{14}$/', $cnpj) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $firstDigit = self::checkDigit(substr($cnpj, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = self::checkDigit(substr($cnpj, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $cnpj[12] === (string) $firstDigit && $cnpj[13] === (string) $secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private static function checkDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $base[$index]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
