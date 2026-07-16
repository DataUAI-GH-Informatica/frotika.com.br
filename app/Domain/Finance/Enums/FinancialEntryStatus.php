<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialEntryStatus: string
{
    case Forecast = 'forecast';
    case Settled = 'settled';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Forecast => 'Previsto',
            self::Settled => 'Liquidado',
            self::Canceled => 'Cancelado',
        };
    }
}
