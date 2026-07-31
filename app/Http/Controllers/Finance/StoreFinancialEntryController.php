<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Actions\CreateManualFinancialEntry;
use App\Domain\Finance\Actions\CreateScheduledFinancialEntries;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Finance\StoreFinancialEntryRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreFinancialEntryController
{
    public function __invoke(
        StoreFinancialEntryRequest $request,
        CreateManualFinancialEntry $action,
        CreateScheduledFinancialEntries $createScheduled,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa antes de lançar.');
        }

        $data = $request->validated();
        $data['type'] = $this->resolveType((int) $data['financial_category_id']);
        $launchMode = (string) ($data['launch_mode'] ?? 'single');
        $message = 'Lançamento registrado com sucesso.';

        if (($data['reference_date'] ?? null) === null) {
            $data['reference_date'] = $data['competence_date'] ?? null;
        }

        if ($launchMode === 'monthly' && ($data['competence_date'] ?? null) === null) {
            $data['competence_date'] = (string) $data['reference_date'];
        }

        if ($launchMode === 'single') {
            $action->execute($company, (int) $user->getKey(), $data);
        } else {
            $result = $createScheduled->execute($company, (int) $user->getKey(), $data);
            $message = $launchMode === 'installment'
                ? sprintf('Parcelamento criado com %d parcela(s) prevista(s).', $result['entries_created'])
                : 'Lançamento mensal recorrente criado com sucesso.';
        }

        return redirect()
            ->route('financial-entries.index')
            ->with('status', $message);
    }

    private function resolveType(int $categoryId): string
    {
        $category = FinancialCategory::query()->find($categoryId);

        return $category?->type->value ?? '';
    }
}
