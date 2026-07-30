<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Fuelings\Models\FuelingImportBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowFuelingImportResultController
{
    public function __invoke(Request $request, string $batch): View
    {
        Gate::authorize('viewAny', Fueling::class);

        // O CompanyScope garante que só o lote da empresa ativa é encontrado.
        $model = FuelingImportBatch::query()
            ->where('uuid', $batch)
            ->firstOrFail();

        return view('fuelings.import-result', [
            'batch' => $model,
        ]);
    }
}
