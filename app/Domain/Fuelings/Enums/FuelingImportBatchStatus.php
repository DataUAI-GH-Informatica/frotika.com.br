<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Enums;

enum FuelingImportBatchStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Processando',
            self::Completed => 'Concluído',
        };
    }
}
