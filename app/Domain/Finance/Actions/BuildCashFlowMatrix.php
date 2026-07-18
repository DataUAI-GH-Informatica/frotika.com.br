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
     * @param  list<int>|null  $financialCategoryIds
     * @param  list<int>|null  $vehicleIds
     * @param  list<string>|null  $statuses
     * @return array{
     *     from: string,
     *     to: string,
     *     include_forecast: bool,
     *     applied_filters: array{
     *         bank_account_ids: list<int>,
     *         financial_category_ids: list<int>,
     *         vehicle_ids: list<int>,
     *         statuses: list<string>
     *     },
     *     totals: array{
     *         opening_balance_cents: int,
     *         revenue_cents: int,
     *         expense_cents: int,
     *         net_cents: int,
     *         closing_balance_cents: int
     *     },
     *     accounts: list<array{
     *         bank_account_id: int,
     *         name: string,
     *         opening_balance_cents: int,
     *         revenue_cents: int,
     *         expense_cents: int,
     *         net_cents: int,
     *         closing_balance_cents: int,
     *         days: list<array{
     *             date: string,
     *             revenue_cents: int,
     *             expense_cents: int,
     *             net_cents: int,
     *             running_balance_cents: int
     *         }>
     *     }>,
     *     unassigned_forecast: array{
     *         revenue_cents: int,
     *         expense_cents: int,
     *         net_cents: int,
     *         days: list<array{
     *             date: string,
     *             revenue_cents: int,
     *             expense_cents: int,
     *             net_cents: int
     *         }>
     *     }
     * }
     */
    public function execute(
        Company $company,
        string $fromDate,
        string $toDate,
        bool $includeForecast = false,
        ?array $bankAccountIds = null,
        ?array $financialCategoryIds = null,
        ?array $vehicleIds = null,
        ?array $statuses = null,
    ): array {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->startOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to_date' => 'A data final deve ser maior ou igual a data inicial.',
            ]);
        }

        $resolvedStatuses = $this->resolveStatuses($includeForecast, $statuses);
        $resolvedIncludeForecast = in_array(FinancialEntryStatus::Forecast->value, $resolvedStatuses, true);
        $resolvedBankAccountIds = $this->normalizeIntegerList($bankAccountIds, 'bank_account_ids');
        $resolvedFinancialCategoryIds = $this->normalizeIntegerList($financialCategoryIds, 'financial_category_ids');
        $resolvedVehicleIds = $this->normalizeIntegerList($vehicleIds, 'vehicle_ids');

        return $this->tenant->runFor($company, function () use ($from, $to, $resolvedIncludeForecast, $resolvedStatuses, $resolvedBankAccountIds, $resolvedFinancialCategoryIds, $resolvedVehicleIds): array {
            $accountsQuery = BankAccount::query()->where('active', true)->orderBy('name');

            if ($resolvedBankAccountIds !== null && $resolvedBankAccountIds !== []) {
                $accountsQuery->whereIn('id', $resolvedBankAccountIds);
            }

            /** @var Collection<int, BankAccount> $accounts */
            $accounts = $accountsQuery->get(['id', 'name', 'initial_balance_cents']);

            // Previstos sem conta-alvo projetam no consolidado, nunca por conta.
            // Só entram quando previstos estão incluídos e não há filtro de conta.
            $includeUnassigned = in_array(FinancialEntryStatus::Forecast->value, $resolvedStatuses, true)
                && ($resolvedBankAccountIds === null || $resolvedBankAccountIds === []);
            $unassigned = $includeUnassigned
                ? $this->buildUnassignedForecast($from, $to, $resolvedFinancialCategoryIds, $resolvedVehicleIds)
                : $this->emptyUnassignedForecast($from, $to);

            $appliedFilters = [
                'bank_account_ids' => $resolvedBankAccountIds ?? [],
                'financial_category_ids' => $resolvedFinancialCategoryIds ?? [],
                'vehicle_ids' => $resolvedVehicleIds ?? [],
                'statuses' => $resolvedStatuses,
            ];

            if ($accounts->isEmpty()) {
                return [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'include_forecast' => $resolvedIncludeForecast,
                    'applied_filters' => $appliedFilters,
                    'totals' => [
                        'opening_balance_cents' => 0,
                        'revenue_cents' => $unassigned['revenue_cents'],
                        'expense_cents' => $unassigned['expense_cents'],
                        'net_cents' => $unassigned['net_cents'],
                        'closing_balance_cents' => $unassigned['net_cents'],
                    ],
                    'accounts' => [],
                    'unassigned_forecast' => $unassigned,
                ];
            }

            $accountIds = $accounts->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $entriesQuery = FinancialEntry::query()
                ->whereIn('bank_account_id', $accountIds)
                ->whereIn('status', $resolvedStatuses);

            if ($resolvedFinancialCategoryIds !== null && $resolvedFinancialCategoryIds !== []) {
                $entriesQuery->whereIn('financial_category_id', $resolvedFinancialCategoryIds);
            }

            if ($resolvedVehicleIds !== null && $resolvedVehicleIds !== []) {
                $entriesQuery->whereIn('vehicle_id', $resolvedVehicleIds);
            }

            /** @var Collection<int, FinancialEntry> $entries */
            $entries = $entriesQuery->get(['bank_account_id', 'type', 'status', 'amount_cents', 'paid_at', 'due_date', 'competence_date']);

            $openingByAccount = [];
            $dayTotalsByAccount = [];
            $globalOpening = 0;
            $globalRevenue = 0;
            $globalExpense = 0;
            $globalClosing = 0;

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
                $accountRevenue = 0;
                $accountExpense = 0;
                $daysPayload = [];

                foreach (CarbonPeriod::create($from, $to) as $date) {
                    $dayKey = $date->toDateString();
                    $dayTotals = $dayTotalsByAccount[$bankAccountId][$dayKey] ?? [
                        'revenue_cents' => 0,
                        'expense_cents' => 0,
                    ];

                    $net = (int) $dayTotals['revenue_cents'] - (int) $dayTotals['expense_cents'];
                    $accountRevenue += (int) $dayTotals['revenue_cents'];
                    $accountExpense += (int) $dayTotals['expense_cents'];
                    $running += $net;

                    $daysPayload[] = [
                        'date' => $dayKey,
                        'revenue_cents' => (int) $dayTotals['revenue_cents'],
                        'expense_cents' => (int) $dayTotals['expense_cents'],
                        'net_cents' => $net,
                        'running_balance_cents' => $running,
                    ];
                }

                $accountNet = $accountRevenue - $accountExpense;
                $globalOpening += $opening;
                $globalRevenue += $accountRevenue;
                $globalExpense += $accountExpense;
                $globalClosing += $running;

                $accountsPayload[] = [
                    'bank_account_id' => $bankAccountId,
                    'name' => (string) $account->name,
                    'opening_balance_cents' => $opening,
                    'revenue_cents' => $accountRevenue,
                    'expense_cents' => $accountExpense,
                    'net_cents' => $accountNet,
                    'closing_balance_cents' => $running,
                    'days' => $daysPayload,
                ];
            }

            $globalRevenue += $unassigned['revenue_cents'];
            $globalExpense += $unassigned['expense_cents'];
            $globalClosing += $unassigned['net_cents'];
            $globalNet = $globalRevenue - $globalExpense;

            return [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'include_forecast' => $resolvedIncludeForecast,
                'applied_filters' => $appliedFilters,
                'totals' => [
                    'opening_balance_cents' => $globalOpening,
                    'revenue_cents' => $globalRevenue,
                    'expense_cents' => $globalExpense,
                    'net_cents' => $globalNet,
                    'closing_balance_cents' => $globalClosing,
                ],
                'accounts' => $accountsPayload,
                'unassigned_forecast' => $unassigned,
            ];
        });
    }

    /**
     * Previstos sem conta-alvo, agregados por data de caixa (due_date com
     * fallback para competence_date), para projetar no saldo consolidado.
     *
     * @param  list<int>|null  $financialCategoryIds
     * @param  list<int>|null  $vehicleIds
     * @return array{revenue_cents: int, expense_cents: int, net_cents: int, days: list<array{date: string, revenue_cents: int, expense_cents: int, net_cents: int}>}
     */
    private function buildUnassignedForecast(
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?array $financialCategoryIds,
        ?array $vehicleIds,
    ): array {
        $query = FinancialEntry::query()
            ->whereNull('bank_account_id')
            ->where('status', FinancialEntryStatus::Forecast->value);

        if ($financialCategoryIds !== null && $financialCategoryIds !== []) {
            $query->whereIn('financial_category_id', $financialCategoryIds);
        }

        if ($vehicleIds !== null && $vehicleIds !== []) {
            $query->whereIn('vehicle_id', $vehicleIds);
        }

        /** @var Collection<int, FinancialEntry> $entries */
        $entries = $query->get(['type', 'status', 'amount_cents', 'paid_at', 'due_date', 'competence_date']);

        $dayTotals = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($entries as $entry) {
            $entryDate = $this->resolveCashDate($entry);

            if ($entryDate === null || $entryDate->lt($from) || $entryDate->gt($to)) {
                continue;
            }

            $signedAmount = $this->signedAmountCents($entry);

            if ($signedAmount === 0) {
                continue;
            }

            $dayKey = $entryDate->toDateString();
            $dayTotals[$dayKey] ??= ['revenue_cents' => 0, 'expense_cents' => 0];

            if ($signedAmount > 0) {
                $dayTotals[$dayKey]['revenue_cents'] += $signedAmount;
                $totalRevenue += $signedAmount;
            } else {
                $dayTotals[$dayKey]['expense_cents'] += abs($signedAmount);
                $totalExpense += abs($signedAmount);
            }
        }

        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dayKey = $date->toDateString();
            $totals = $dayTotals[$dayKey] ?? ['revenue_cents' => 0, 'expense_cents' => 0];
            $days[] = [
                'date' => $dayKey,
                'revenue_cents' => (int) $totals['revenue_cents'],
                'expense_cents' => (int) $totals['expense_cents'],
                'net_cents' => (int) $totals['revenue_cents'] - (int) $totals['expense_cents'],
            ];
        }

        return [
            'revenue_cents' => $totalRevenue,
            'expense_cents' => $totalExpense,
            'net_cents' => $totalRevenue - $totalExpense,
            'days' => $days,
        ];
    }

    /**
     * @return array{revenue_cents: int, expense_cents: int, net_cents: int, days: list<array{date: string, revenue_cents: int, expense_cents: int, net_cents: int}>}
     */
    private function emptyUnassignedForecast(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $days[] = [
                'date' => $date->toDateString(),
                'revenue_cents' => 0,
                'expense_cents' => 0,
                'net_cents' => 0,
            ];
        }

        return [
            'revenue_cents' => 0,
            'expense_cents' => 0,
            'net_cents' => 0,
            'days' => $days,
        ];
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return list<int>|null
     */
    private function normalizeIntegerList(?array $values, string $field): ?array
    {
        if ($values === null) {
            return null;
        }

        if ($values === []) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            if (! is_int($value)) {
                throw ValidationException::withMessages([
                    $field => 'Filtro invalido. Use apenas identificadores inteiros.',
                ]);
            }

            if ($value < 1) {
                throw ValidationException::withMessages([
                    $field => 'Filtro invalido. Identificadores devem ser maiores que zero.',
                ]);
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  array<int, mixed>|null  $statuses
     * @return list<string>
     */
    private function resolveStatuses(bool $includeForecast, ?array $statuses): array
    {
        if ($statuses === null || $statuses === []) {
            return $includeForecast
                ? [FinancialEntryStatus::Settled->value, FinancialEntryStatus::Forecast->value]
                : [FinancialEntryStatus::Settled->value];
        }

        $allowedStatuses = [FinancialEntryStatus::Settled->value, FinancialEntryStatus::Forecast->value];
        $resolvedStatuses = [];

        foreach ($statuses as $status) {
            if (! is_string($status) || ! in_array($status, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'statuses' => 'Status invalido para o fluxo de caixa. Use settled e/ou forecast.',
                ]);
            }

            $resolvedStatuses[] = $status;
        }

        return array_values(array_unique($resolvedStatuses));
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
