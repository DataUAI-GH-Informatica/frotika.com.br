<?php

declare(strict_types=1);

namespace App\Support\Money;

use InvalidArgumentException;

final class Apportionment
{
    /**
     * @param  array<int, int>  $weights
     * @return array<int, int>
     */
    public static function distribute(int $totalCents, array $weights): array
    {
        if ($weights === []) {
            return [];
        }

        foreach ($weights as $weight) {
            if ($weight < 0) {
                throw new InvalidArgumentException('Weights must be zero or positive integers.');
            }
        }

        $weightSum = array_sum($weights);

        if ($weightSum === 0) {
            return array_fill(0, count($weights), 0);
        }

        if ($totalCents === 0) {
            return array_fill(0, count($weights), 0);
        }

        $sign = $totalCents < 0 ? -1 : 1;
        $absoluteTotal = abs($totalCents);

        $parts = [];
        $allocated = 0;

        foreach ($weights as $index => $weight) {
            $product = $absoluteTotal * $weight;
            $base = intdiv($product, $weightSum);
            $remainder = $product % $weightSum;

            $parts[$index] = [
                'base' => $base,
                'remainder' => $remainder,
                'weight' => $weight,
                'index' => $index,
            ];

            $allocated += $base;
        }

        $missing = $absoluteTotal - $allocated;

        uasort(
            $parts,
            static function (array $left, array $right): int {
                if ($left['remainder'] !== $right['remainder']) {
                    return $right['remainder'] <=> $left['remainder'];
                }

                if ($left['weight'] !== $right['weight']) {
                    return $right['weight'] <=> $left['weight'];
                }

                return $right['index'] <=> $left['index'];
            }
        );

        foreach ($parts as &$part) {
            if ($missing === 0) {
                break;
            }

            $part['base']++;
            $missing--;
        }
        unset($part);

        usort(
            $parts,
            static fn (array $left, array $right): int => $left['index'] <=> $right['index']
        );

        return array_map(
            static fn (array $part): int => $part['base'] * $sign,
            $parts
        );
    }

    /**
     * @return array<int, int>
     */
    public static function equally(int $totalCents, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        return self::distribute($totalCents, array_fill(0, $parts, 1));
    }
}
