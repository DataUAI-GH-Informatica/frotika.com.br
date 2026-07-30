<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Enums;

enum FuelingImportItemStatus: string
{
    case Imported = 'imported';
    case Ignored = 'ignored';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Imported => 'Importada',
            self::Ignored => 'Ignorada',
            self::Failed => 'Falhou',
        };
    }
}
