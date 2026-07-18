<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenances;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Maintenances\Models\Maintenance;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class ShowEditMaintenanceController
{
    public function __invoke(int $maintenance): View
    {
        $model = Maintenance::query()->findOrFail($maintenance);

        Gate::authorize('update', $model);

        return view('maintenances.edit', [
            'maintenance' => $model,
            'vehicles' => Vehicle::query()->orderBy('plate')->get(['id', 'plate']),
        ]);
    }
}
