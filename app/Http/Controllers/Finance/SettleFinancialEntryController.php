<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Actions\SettleFinancialEntry;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Finance\SettleFinancialEntryRequest;
use Illuminate\Http\RedirectResponse;

final class SettleFinancialEntryController
{
    public function __invoke(SettleFinancialEntryRequest $request, int $entry, SettleFinancialEntry $action): RedirectResponse
    {
        $model = FinancialEntry::query()->findOrFail($entry);
        $company = Company::query()->findOrFail($model->getAttribute('company_id'));

        $result = $action->execute($company, (int) $model->getKey(), [
            'bank_account_id' => (int) $request->validated('bank_account_id'),
            'paid_at' => (string) $request->validated('paid_at'),
            'payment_method' => $request->validated('payment_method'),
            'paid_amount_cents' => $request->validated('paid_amount_cents'),
            'discount_cents' => $request->validated('discount_cents'),
            'interest_cents' => $request->validated('interest_cents'),
        ]);

        $message = $result['is_partial']
            ? 'Baixa parcial registrada. O saldo pendente foi mantido como previsto.'
            : 'Baixa registrada com sucesso.';

        return redirect()
            ->route('financial-entries.show', ['entry' => $model->getKey()])
            ->with('status', $message);
    }
}
