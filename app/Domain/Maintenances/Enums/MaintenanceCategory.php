<?php

declare(strict_types=1);

namespace App\Domain\Maintenances\Enums;

enum MaintenanceCategory: string
{
    case Engine = 'engine';
    case Transmission = 'transmission';
    case Brakes = 'brakes';
    case Suspension = 'suspension';
    case Electrical = 'electrical';
    case Tires = 'tires';
    case Bodywork = 'bodywork';
    case Trailer = 'trailer';
    case Documentation = 'documentation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Engine => 'Motor',
            self::Transmission => 'Transmissão',
            self::Brakes => 'Freios',
            self::Suspension => 'Suspensão',
            self::Electrical => 'Elétrica',
            self::Tires => 'Pneus',
            self::Bodywork => 'Funilaria',
            self::Trailer => 'Carreta',
            self::Documentation => 'Documentação',
            self::Other => 'Outros',
        };
    }
}
