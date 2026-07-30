<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Actions\ReverseFinancialEntrySettlement;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Finance\ReverseFinancialEntrySettlementRequest;
use Illuminate\Http\RedirectResponse;

final class ReverseFinancialEntrySettlementController
{
    public function __invoke(ReverseFinancialEntrySettlementRequest $request, int $entry, ReverseFinancialEntrySettlement $action): RedirectResponse
    {
        $model = FinancialEntry::query()->findOrFail($entry);
        $company = Company::query()->findOrFail($model->getAttribute('company_id'));

        $action->execute($company, (int) $model->getKey());

        return redirect()
            ->route('financial-entries.show', ['entry' => $model->getKey()])
            ->with('status', 'Baixa revertida com sucesso.');
    }
}
