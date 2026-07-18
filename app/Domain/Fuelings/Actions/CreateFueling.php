<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Actions;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Data\FuelingData;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Format;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateFueling
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly FuelConsumptionCalculator $consumption,
    ) {}

    public function execute(User $actor, Company $company, FuelingData $data): Fueling
    {
        Gate::forUser($actor)->authorize('create', Fueling::class);

        return $this->tenant->runFor($company, function () use ($actor, $company, $data): Fueling {
            $vehicle = Vehicle::query()->find($data->vehicleId);

            if (! $vehicle instanceof Vehicle) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Selecione um veículo válido da empresa ativa.',
                ]);
            }

            $this->guardOdometer($vehicle, $data->odometer, $data->allowOdometerRollback);

            $attributes = $data->toAttributes();
            $attributes['company_id'] = $company->getKey();
            $attributes['created_by'] = $actor->getKey();

            /** @var Fueling $fueling */
            $fueling = Fueling::query()->create($attributes);

            if ($data->odometer > (int) $vehicle->getAttribute('odometer_current')) {
                $vehicle->forceFill(['odometer_current' => $data->odometer])->save();
            }

            $this->consumption->recalculateForVehicle((int) $vehicle->getKey());

            return $fueling->refresh();
        });
    }

    private function guardOdometer(Vehicle $vehicle, int $odometer, bool $allowRollback): void
    {
        if ($allowRollback) {
            return;
        }

        $lastKnown = (int) $vehicle->getAttribute('odometer_current');

        $lastFuelingOdometer = (int) Fueling::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->max('odometer');

        $lastKnown = max($lastKnown, $lastFuelingOdometer);

        if ($odometer < $lastKnown) {
            throw ValidationException::withMessages([
                'odometer' => sprintf(
                    'Odômetro (%s) menor que o último conhecido (%s). Marque a correção para confirmar.',
                    Format::km($odometer),
                    Format::km($lastKnown),
                ),
            ]);
        }
    }
}
