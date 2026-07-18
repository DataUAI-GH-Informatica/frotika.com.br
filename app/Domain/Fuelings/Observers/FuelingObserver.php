<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Observers;

use App\Domain\Finance\Actions\EntrySynchronizer;
use App\Domain\Fuelings\Models\Fueling;

/**
 * Mantém o lançamento financeiro do abastecimento em sincronia (blueprint 6.3):
 * toda criação, atualização, exclusão (soft delete) ou restauração recalcula a
 * despesa via EntrySynchronizer — o abastecimento nunca vira número no DRE por
 * fora.
 */
final class FuelingObserver
{
    public function __construct(private readonly EntrySynchronizer $synchronizer) {}

    public function saved(Fueling $fueling): void
    {
        $this->synchronizer->syncFromFueling($fueling);
    }

    public function deleted(Fueling $fueling): void
    {
        $this->synchronizer->syncFromFueling($fueling);
    }

    public function restored(Fueling $fueling): void
    {
        $this->synchronizer->syncFromFueling($fueling);
    }
}
