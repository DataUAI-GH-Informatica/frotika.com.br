<?php

declare(strict_types=1);

namespace App\Domain\Maintenances\Enums;

enum MaintenanceType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';
    case Predictive = 'predictive';
    case Tire = 'tire';
    case Overhaul = 'overhaul';
    case Accident = 'accident';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventiva',
            self::Corrective => 'Corretiva',
            self::Predictive => 'Preditiva',
            self::Tire => 'Pneus',
            self::Overhaul => 'Revisão geral',
            self::Accident => 'Sinistro',
        };
    }

    /**
     * Preventiva é custo fixo do veículo (categoria 4.3); as demais são custo
     * variável de manutenção corretiva (categoria 3.4) — blueprint 6.3.
     */
    public function isFixedCost(): bool
    {
        return $this === self::Preventive;
    }
}
