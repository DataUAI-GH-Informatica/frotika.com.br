<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum RecurrenceKind: string
{
    case Recurring = 'recurring';
    case Installment = 'installment';

    public function label(): string
    {
        return match ($this) {
            self::Recurring => 'Recorrente',
            self::Installment => 'Parcelado',
        };
    }
}
