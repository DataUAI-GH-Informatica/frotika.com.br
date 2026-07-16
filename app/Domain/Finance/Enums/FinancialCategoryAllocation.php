<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialCategoryAllocation: string
{
    case VehicleDirect = 'vehicle_direct';
    case Apportioned = 'apportioned';
    case NonVehicle = 'non_vehicle';

    public function label(): string
    {
        return match ($this) {
            self::VehicleDirect => 'Direto do veiculo',
            self::Apportioned => 'Rateado',
            self::NonVehicle => 'Nao veicular',
        };
    }
}
