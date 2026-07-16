<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class BuildCashFlowMatrix
{
    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  list<int>|null  $bankAccountIds
     * @return array{
     *     from: string,
     *     to: string,
     *     include_forecast: bool,
     *     accounts: list<array{
     *         bank_account_id: int,
     *         name: string,
     *         opening_balance_cents: int,
     *         closing_balance_cents: int,
     *         days: list<array{
     *             date: string,
     *             revenue_cents: int,
     *             expense_cents: int,
     *             net_cents: int,
     *             running_balance_cents: int
     *         }>
     *     }>
     * }
     */
    public function execute(
        Company $company,
        string $fromDate,
        string $toDate,
        bool $includeForecast = false,
        ?array $bankAccountIds = null,
    ): array {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->startOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to_date' => 'A data final deve ser maior ou igual a data inicial.',
            ]);
        }

        return $this->tenant->runFor($company, function () use ($from, $to, $includeForecast, $bankAccountIds): array {
            $accountsQuery = BankAccount::query()->where('active', true)->orderBy('name');

            if ($bankAccountIds !== null && $bankAccountIds !== []) {
                $accountsQuery->whereIn('id', $bankAccountIds);
            }

            /** @var Collection<int, BankAccount> $accounts */
            $accounts = $accountsQuery->get(['id', 'name', 'initial_balance_cents']);

            if ($accounts->isEmpty()) {
                return [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'include_forecast' => $includeForecast,
                    'accounts' => [],
                ];
            }

            $accountIds = $accounts->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $statuses = $includeForecast
                ? [FinancialEntryStatus::Settled->value, FinancialEntryStatus::Forecast->value]
                : [FinancialEntryStatus::Settled->value];

            /** @var Collection<int, FinancialEntry> $entries */
            $entries = FinancialEntry::query()
                ->whereIn('bank_account_id', $accountIds)
                ->whereIn('status', $statuses)
                ->get(['bank_account_id', 'type', 'status', 'amount_cents', 'paid_at', 'due_date', 'competence_date']);

            $openingByAccount = [];
            $dayTotalsByAccount = [];

            foreach ($entries as $entry) {
                $bankAccountId = (int) $entry->bank_account_id;
                $entryDate = $this->resolveCashDate($entry);

                if ($entryDate === null) {
                    continue;
                }

                $signedAmount = $this->signedAmountCents($entry);

                if ($signedAmount === 0) {
                    continue;
                }

                if ($entryDate->lt($from)) {
                    $openingByAccount[$bankAccountId] = ($openingByAccount[$bankAccountId] ?? 0) + $signedAmount;

                    continue;
                }

                if ($entryDate->gt($to)) {
                    continue;
                }

                $dayKey = $entryDate->toDateString();
                $dayTotalsByAccount[$bankAccountId] ??= [];
                $dayTotalsByAccount[$bankAccountId][$dayKey] ??= [
                    'revenue_cents' => 0,
                    'expense_cents' => 0,
                ];

                if ($signedAmount > 0) {
                    $dayTotalsByAccount[$bankAccountId][$dayKey]['revenue_cents'] += $signedAmount;
                } else {
                    $dayTotalsByAccount[$bankAccountId][$dayKey]['expense_cents'] += abs($signedAmount);
                }
            }

            $accountsPayload = [];

            foreach ($accounts as $account) {
                $bankAccountId = (int) $account->getKey();
                $opening = (int) $account->initial_balance_cents + ($openingByAccount[$bankAccountId] ?? 0);
                $running = $opening;
                $daysPayload = [];

                foreach (CarbonPeriod::create($from, $to) as $date) {
                    $dayKey = $date->toDateString();
                    $dayTotals = $dayTotalsByAccount[$bankAccountId][$dayKey] ?? [
                        'revenue_cents' => 0,
                        'expense_cents' => 0,
                    ];

                    $net = (int) $dayTotals['revenue_cents'] - (int) $dayTotals['expense_cents'];
                    $running += $net;

                    $daysPayload[] = [
                        'date' => $dayKey,
                        'revenue_cents' => (int) $dayTotals['revenue_cents'],
                        'expense_cents' => (int) $dayTotals['expense_cents'],
                        'net_cents' => $net,
                        'running_balance_cents' => $running,
                    ];
                }

                $accountsPayload[] = [
                    'bank_account_id' => $bankAccountId,
                    'name' => (string) $account->name,
                    'opening_balance_cents' => $opening,
                    'closing_balance_cents' => $running,
                    'days' => $daysPayload,
                ];
            }

            return [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'include_forecast' => $includeForecast,
                'accounts' => $accountsPayload,
            ];
        });
    }

    private function resolveCashDate(FinancialEntry $entry): ?CarbonImmutable
    {
        if ($entry->status === FinancialEntryStatus::Settled) {
            if ($entry->paid_at === null) {
                return null;
            }

            return CarbonImmutable::parse((string) $entry->paid_at)->startOfDay();
        }

        if ($entry->status === FinancialEntryStatus::Forecast) {
            if ($entry->due_date !== null) {
                return CarbonImmutable::parse((string) $entry->due_date)->startOfDay();
            }

            return CarbonImmutable::parse((string) $entry->competence_date)->startOfDay();
        }

        return null;
    }

    private function signedAmountCents(FinancialEntry $entry): int
    {
        $amount = (int) $entry->amount_cents;

        return match ($entry->type) {
            FinancialEntryType::Revenue => $amount,
            FinancialEntryType::Expense => -$amount,
            default => 0,
        };
    }
}
