<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenances;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Maintenances\Models\Maintenance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ShowCreateMaintenanceController
{
    public function __invoke(): View|RedirectResponse
    {
        Gate::authorize('create', Maintenance::class);

        $vehicles = Vehicle::query()->orderBy('plate')->get(['id', 'plate']);

        if ($vehicles->isEmpty()) {
            return redirect()
                ->route('vehicles.index')
                ->with('warning', 'Cadastre um veículo antes de lançar manutenções.');
        }

        return view('maintenances.create', [
            'maintenance' => null,
            'vehicles' => $vehicles,
        ]);
    }
}
