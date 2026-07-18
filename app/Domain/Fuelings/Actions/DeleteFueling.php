<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Actions;

use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;

final class DeleteFueling
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly FuelConsumptionCalculator $consumption,
    ) {}

    public function execute(User $actor, Company $company, Fueling $fueling): void
    {
        Gate::forUser($actor)->authorize('delete', $fueling);

        $this->tenant->runFor($company, function () use ($fueling): void {
            $vehicleId = (int) $fueling->getAttribute('vehicle_id');

            // Soft delete dispara o observer, que cancela o lançamento financeiro.
            $fueling->delete();

            $this->consumption->recalculateForVehicle($vehicleId);
        });
    }
}
