<?php

declare(strict_types=1);

namespace App\Domain\Maintenances\Enums;

enum MaintenanceStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluída',
            self::Canceled => 'Cancelada',
        };
    }
}
