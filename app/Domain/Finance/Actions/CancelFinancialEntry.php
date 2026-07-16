<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

final class CancelFinancialEntry
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly RecalculateBankAccountCurrentBalance $recalculateBankAccountCurrentBalance,
    ) {}

    public function execute(Company $company, int $entryId): void
    {
        $this->tenant->runFor($company, function () use ($entryId, $company): void {
            $entry = FinancialEntry::query()->find($entryId);

            if ($entry === null) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lancamento financeiro invalido para a empresa ativa.',
                ]);
            }

            if ($entry->sourceable_type !== null || $entry->sourceable_id !== null) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lancamentos sincronizados devem ser cancelados na origem.',
                ]);
            }

            if ($entry->status === FinancialEntryStatus::Canceled) {
                return;
            }

            $previousBankAccountId = $entry->bank_account_id !== null ? (int) $entry->bank_account_id : null;

            $entry->forceFill([
                'status' => FinancialEntryStatus::Canceled,
                'paid_at' => null,
                'bank_account_id' => null,
                'reconciled_at' => null,
            ])->save();

            if ($previousBankAccountId !== null) {
                $this->recalculateBankAccountCurrentBalance->execute($company, $previousBankAccountId);
            }
        });
    }
}
