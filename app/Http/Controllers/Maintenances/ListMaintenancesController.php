<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenances;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Maintenances\Enums\MaintenanceStatus;
use App\Domain\Maintenances\Enums\MaintenanceType;
use App\Domain\Maintenances\Models\Maintenance;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ListMaintenancesController
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Maintenance::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $filters = [
            'vehicle' => $request->integer('vehicle') ?: null,
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $typeFilter = MaintenanceType::tryFrom($filters['type']);
        $statusFilter = MaintenanceStatus::tryFrom($filters['status']);

        $applyFilters = function ($query) use ($filters, $typeFilter, $statusFilter) {
            return $query
                ->when($filters['vehicle'] !== null, fn ($q) => $q->where('vehicle_id', $filters['vehicle']))
                ->when($typeFilter !== null, fn ($q) => $q->where('type', $typeFilter?->value))
                ->when($statusFilter !== null, fn ($q) => $q->where('status', $statusFilter?->value))
                ->when($filters['from'] !== '', fn ($q) => $q->whereDate('opened_at', '>=', $filters['from']))
                ->when($filters['to'] !== '', fn ($q) => $q->whereDate('opened_at', '<=', $filters['to']));
        };

        $maintenances = $applyFilters(Maintenance::query()->with('vehicle:id,plate'))
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $totalCents = (int) $applyFilters(Maintenance::query())
            ->where('status', '!=', MaintenanceStatus::Canceled->value)
            ->sum('total_cents');

        return view('maintenances.index', [
            'maintenances' => $maintenances,
            'totalCents' => $totalCents,
            'filters' => $filters,
            'vehicles' => Vehicle::query()->orderBy('plate')->get(['id', 'plate']),
            'types' => MaintenanceType::cases(),
            'statuses' => MaintenanceStatus::cases(),
            'canManage' => Gate::allows('create', Maintenance::class),
        ]);
    }
}
