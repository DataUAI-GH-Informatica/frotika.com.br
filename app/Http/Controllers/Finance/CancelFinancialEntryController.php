<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Actions\CancelFinancialEntry;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Finance\CancelFinancialEntryRequest;
use Illuminate\Http\RedirectResponse;

final class CancelFinancialEntryController
{
    public function __invoke(CancelFinancialEntryRequest $request, int $entry, CancelFinancialEntry $action): RedirectResponse
    {
        $model = FinancialEntry::query()->findOrFail($entry);

        $company = Company::query()->findOrFail($model->getAttribute('company_id'));

        $action->execute(
            $company,
            (int) $model->getKey(),
            (string) $request->validated('scope'),
        );

        return redirect()
            ->route('financial-entries.index')
            ->with('status', 'Lançamento cancelado.');
    }
}
