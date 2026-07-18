<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Enums;

enum FuelProduct: string
{
    case DieselS10 = 'diesel_s10';
    case DieselS500 = 'diesel_s500';
    case Arla32 = 'arla32';
    case Gasoline = 'gasoline';
    case Ethanol = 'ethanol';
    case Cng = 'cng';
    case Oil = 'oil';

    public function label(): string
    {
        return match ($this) {
            self::DieselS10 => 'Diesel S10',
            self::DieselS500 => 'Diesel S500',
            self::Arla32 => 'Arla 32',
            self::Gasoline => 'Gasolina',
            self::Ethanol => 'Etanol',
            self::Cng => 'GNV',
            self::Oil => 'Óleo lubrificante',
        };
    }

    /**
     * Combustível de tração: entra no cálculo de km/l. Arla 32 e óleo NUNCA
     * entram (regra 8) — Arla é consumível de emissão e óleo é lubrificante.
     */
    public function isFuel(): bool
    {
        return $this !== self::Arla32 && $this !== self::Oil;
    }
}
