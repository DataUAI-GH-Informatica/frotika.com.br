<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

final class RecalculateBankAccountCurrentBalance
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function execute(Company $company, int $bankAccountId): int
    {
        return $this->tenant->runFor($company, function () use ($bankAccountId): int {
            $bankAccount = BankAccount::query()->find($bankAccountId);

            if ($bankAccount === null) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Conta bancaria invalida para a empresa ativa.',
                ]);
            }

            $initialBalance = (int) $bankAccount->getAttribute('initial_balance_cents');

            $revenueTotal = (int) FinancialEntry::query()
                ->where('bank_account_id', $bankAccountId)
                ->where('status', FinancialEntryStatus::Settled->value)
                ->whereNotNull('paid_at')
                ->where('type', FinancialEntryType::Revenue->value)
                ->sum('amount_cents');

            $expenseTotal = (int) FinancialEntry::query()
                ->where('bank_account_id', $bankAccountId)
                ->where('status', FinancialEntryStatus::Settled->value)
                ->whereNotNull('paid_at')
                ->where('type', FinancialEntryType::Expense->value)
                ->sum('amount_cents');

            $currentBalance = $initialBalance + $revenueTotal - $expenseTotal;

            $bankAccount->forceFill([
                'current_balance_cents' => $currentBalance,
            ])->save();

            return $currentBalance;
        });
    }
}
