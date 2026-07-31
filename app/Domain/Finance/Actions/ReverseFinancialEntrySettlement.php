<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

/**
 * Estorna a baixa de um lançamento liquidado, voltando-o para previsto.
 * Reverte apenas o fato de caixa (status/pagamento) e recalcula o saldo da conta.
 */
final class ReverseFinancialEntrySettlement
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
                    'entry_id' => 'Lançamento financeiro inválido para a empresa ativa.',
                ]);
            }

            if ($entry->transfer_pair_id !== null) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Transferências entre contas não permitem estorno de baixa.',
                ]);
            }

            if ($entry->status === FinancialEntryStatus::Canceled) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lançamento cancelado não pode ter baixa revertida.',
                ]);
            }

            if ($entry->status === FinancialEntryStatus::Forecast) {
                return;
            }

            $previousBankAccountId = $entry->bank_account_id !== null ? (int) $entry->bank_account_id : null;

            $entry->forceFill([
                'status' => FinancialEntryStatus::Forecast->value,
                'paid_at' => null,
                'payment_method' => null,
                'settlement_discount_cents' => 0,
                'settlement_interest_cents' => 0,
                'reconciled_at' => null,
            ])->save();

            if ($previousBankAccountId !== null) {
                $this->recalculateBankAccountCurrentBalance->execute($company, $previousBankAccountId);
            }
        });
    }
}
