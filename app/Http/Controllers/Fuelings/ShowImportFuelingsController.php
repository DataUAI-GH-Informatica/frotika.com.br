<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fuelings\Import\FuelingImportSheet;
use App\Domain\Fuelings\Models\Fueling;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowImportFuelingsController
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('create', Fueling::class);

        return view('fuelings.import', [
            'maxRows' => FuelingImportSheet::MAX_ROWS,
            'requiredColumns' => FuelingImportSheet::requiredColumns(),
        ]);
    }
}
