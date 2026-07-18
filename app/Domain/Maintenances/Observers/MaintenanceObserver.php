<?php

declare(strict_types=1);

namespace App\Domain\Maintenances\Observers;

use App\Domain\Finance\Actions\EntrySynchronizer;
use App\Domain\Maintenances\Models\Maintenance;

/**
 * Mantém o lançamento financeiro da manutenção em sincronia (blueprint 6.3):
 * criação, atualização, cancelamento (status/soft delete) ou restauração
 * recalculam a despesa via EntrySynchronizer — a manutenção nunca vira número
 * no DRE por fora.
 */
final class MaintenanceObserver
{
    public function __construct(private readonly EntrySynchronizer $synchronizer) {}

    public function saved(Maintenance $maintenance): void
    {
        $this->synchronizer->syncFromMaintenance($maintenance);
    }

    public function deleted(Maintenance $maintenance): void
    {
        $this->synchronizer->syncFromMaintenance($maintenance);
    }

    public function restored(Maintenance $maintenance): void
    {
        $this->synchronizer->syncFromMaintenance($maintenance);
    }
}
