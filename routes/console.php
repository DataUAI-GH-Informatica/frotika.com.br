<?php

use App\Domain\Finance\Actions\RecalculateBankAccountCurrentBalance;
use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('frotika:recalculate-balances {--company=} {--dry-run}', function (
    TenantContext $tenant,
    RecalculateBankAccountCurrentBalance $recalculate,
): int {
    $companyOption = $this->option('company');
    $companyId = is_numeric($companyOption) ? (int) $companyOption : null;
    $dryRun = (bool) $this->option('dry-run');

    $companies = Company::query()
        ->when($companyId !== null, fn ($query) => $query->whereKey($companyId))
        ->get();

    if ($companies->isEmpty()) {
        $this->error('Nenhuma empresa encontrada para reconciliacao.');

        return self::FAILURE;
    }

    $processedAccounts = 0;
    $updatedAccounts = 0;
    $divergentAccounts = 0;

    foreach ($companies as $company) {
        $bankAccounts = $tenant->runFor($company, fn () => BankAccount::query()->get());

        foreach ($bankAccounts as $bankAccount) {
            $processedAccounts++;

            $current = (int) $bankAccount->current_balance_cents;

            $expected = $tenant->runFor($company, function () use ($bankAccount): int {
                $initial = (int) $bankAccount->initial_balance_cents;

                $revenueTotal = (int) FinancialEntry::query()
                    ->where('bank_account_id', $bankAccount->getKey())
                    ->where('status', FinancialEntryStatus::Settled->value)
                    ->whereNotNull('paid_at')
                    ->where('type', FinancialEntryType::Revenue->value)
                    ->sum('amount_cents');

                $expenseTotal = (int) FinancialEntry::query()
                    ->where('bank_account_id', $bankAccount->getKey())
                    ->where('status', FinancialEntryStatus::Settled->value)
                    ->whereNotNull('paid_at')
                    ->where('type', FinancialEntryType::Expense->value)
                    ->sum('amount_cents');

                return $initial + $revenueTotal - $expenseTotal;
            });

            if ($current !== $expected) {
                $divergentAccounts++;
            }

            if (! $dryRun) {
                $newBalance = $recalculate->execute($company, (int) $bankAccount->getKey());

                if ($newBalance !== $current) {
                    $updatedAccounts++;
                }
            }
        }
    }

    $modeLabel = $dryRun ? 'dry-run' : 'apply';

    $this->info(sprintf(
        'Reconciliacao concluida (%s): empresas=%d, contas=%d, divergentes=%d, atualizadas=%d',
        $modeLabel,
        $companies->count(),
        $processedAccounts,
        $divergentAccounts,
        $updatedAccounts,
    ));

    return self::SUCCESS;
})->purpose('Recalcula e reconcilia current_balance_cents das contas bancarias');
