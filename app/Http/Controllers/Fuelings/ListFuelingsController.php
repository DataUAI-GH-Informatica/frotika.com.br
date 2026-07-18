<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Enums\FuelProduct;
use App\Domain\Fuelings\Models\Fueling;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ListFuelingsController
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Fueling::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $filters = [
            'vehicle' => $request->integer('vehicle') ?: null,
            'product' => (string) $request->query('product', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $productFilter = FuelProduct::tryFrom($filters['product']);

        $fuelings = Fueling::query()
            ->with('vehicle:id,plate')
            ->when($filters['vehicle'] !== null, fn ($query) => $query->where('vehicle_id', $filters['vehicle']))
            ->when($productFilter !== null, fn ($query) => $query->where('product', $productFilter?->value))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('fueled_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('fueled_at', '<=', $filters['to']))
            ->orderByDesc('fueled_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'liters' => (float) Fueling::query()
                ->when($filters['vehicle'] !== null, fn ($query) => $query->where('vehicle_id', $filters['vehicle']))
                ->when($productFilter !== null, fn ($query) => $query->where('product', $productFilter?->value))
                ->when($filters['from'] !== '', fn ($query) => $query->whereDate('fueled_at', '>=', $filters['from']))
                ->when($filters['to'] !== '', fn ($query) => $query->whereDate('fueled_at', '<=', $filters['to']))
                ->sum('liters'),
            'total_cents' => (int) Fueling::query()
                ->when($filters['vehicle'] !== null, fn ($query) => $query->where('vehicle_id', $filters['vehicle']))
                ->when($productFilter !== null, fn ($query) => $query->where('product', $productFilter?->value))
                ->when($filters['from'] !== '', fn ($query) => $query->whereDate('fueled_at', '>=', $filters['from']))
                ->when($filters['to'] !== '', fn ($query) => $query->whereDate('fueled_at', '<=', $filters['to']))
                ->sum('total_cents'),
        ];

        return view('fuelings.index', [
            'fuelings' => $fuelings,
            'totals' => $totals,
            'filters' => $filters,
            'vehicles' => Vehicle::query()->orderBy('plate')->get(['id', 'plate']),
            'products' => FuelProduct::cases(),
            'canManage' => Gate::allows('create', Fueling::class),
        ]);
    }
}
