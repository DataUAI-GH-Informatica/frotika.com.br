<?php

declare(strict_types=1);

namespace App\Domain\Reports\Dre;

use App\Domain\Finance\Enums\FinancialCategoryAllocation;
use App\Domain\Finance\Enums\FinancialCategoryDreGroup;
use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Fleet\Enums\VehicleStatus;
use App\Domain\Fleet\Enums\VehicleType;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Support\Money\Apportionment;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\ValidationException;

final class DreBuilder
{
    private const METHOD_BY_KM = 'by_km';

    private const METHOD_BY_REVENUE = 'by_revenue';

    private const METHOD_EQUAL = 'equal';

    private const METHOD_NONE = 'none';

    /**
     * @var list<string>
     */
    private const VALID_METHODS = [
        self::METHOD_BY_KM,
        self::METHOD_BY_REVENUE,
        self::METHOD_EQUAL,
        self::METHOD_NONE,
    ];

    /**
     * @var list<string>
     */
    private const ELIGIBLE_VEHICLE_TYPES = [
        VehicleType::Tractor->value,
        VehicleType::Truck->value,
        VehicleType::Toco->value,
        VehicleType::Vuc->value,
    ];

    /**
     * @var list<string>
     */
    private const INELIGIBLE_VEHICLE_STATUSES = [
        VehicleStatus::Inactive->value,
        VehicleStatus::Sold->value,
    ];

    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  list<int>|null  $vehicleIds
     * @return array{
     *     from: string,
     *     to: string,
     *     apportionment: array{
     *         method: string,
     *         basis_total: int,
     *         divisor_zero: bool,
     *         warnings: list<string>
     *     },
     *     totals: array{
     *         vehicles_count: int,
     *         groups_cents: array<string, int>,
     *         gross_revenue_cents: int,
     *         deductions_cents: int,
     *         net_revenue_cents: int,
     *         variable_cost_cents: int,
     *         contribution_margin_cents: int,
     *         fixed_cost_cents: int,
     *         operational_result_cents: int,
     *         admin_expense_cents: int,
     *         financial_expense_cents: int,
     *         net_result_cents: int
     *     },
     *     vehicles: list<array{
     *         vehicle_id: int,
     *         plate: string,
     *         type: string|null,
     *         status: string|null,
     *         groups_cents: array<string, int>,
     *         metrics: array{
     *             gross_revenue_cents: int,
     *             deductions_cents: int,
     *             net_revenue_cents: int,
     *             variable_cost_cents: int,
     *             contribution_margin_cents: int,
     *             fixed_cost_cents: int,
     *             operational_result_cents: int,
     *             admin_expense_cents: int,
     *             financial_expense_cents: int,
     *             net_result_cents: int
     *         },
     *         apportionment: array{
     *             basis_value: int,
     *             basis_percent: float
     *         }
     *     }>
     * }
     */
    public function execute(
        Company $company,
        string $fromDate,
        string $toDate,
        ?array $vehicleIds = null,
    ): array {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->startOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to_date' => 'A data final deve ser maior ou igual a data inicial.',
            ]);
        }

        $resolvedVehicleIds = $this->normalizeIntegerList($vehicleIds, 'vehicle_ids');

        return $this->tenant->runFor($company, function () use ($company, $from, $to, $resolvedVehicleIds): array {
            $directByVehicleGroup = $this->loadDirectByVehicleGroup($from, $to);
            $apportionedByGroup = $this->loadApportionedByGroup($from, $to);
            $eligibleVehicles = $this->loadEligibleVehiclesForApportionment();
            $method = $this->resolveApportionmentMethod($company);
            $weightsByVehicle = $this->resolveWeightsByVehicle($method, $eligibleVehicles, $directByVehicleGroup, $from, $to);
            $apportionedByVehicleGroup = $this->apportionByVehicleGroup($apportionedByGroup, $weightsByVehicle);

            $vehicleIdsForOutput = $this->resolveVehicleIdsForOutput(
                $resolvedVehicleIds,
                $directByVehicleGroup,
                $weightsByVehicle,
            );

            $vehicles = $this->loadVehiclesForOutput($vehicleIdsForOutput);
            $basisTotal = array_sum($weightsByVehicle);
            $warnings = [];

            if ($method !== self::METHOD_NONE && $basisTotal === 0 && $apportionedByGroup !== []) {
                $warnings[] = 'Sem base de rateio no período; despesas rateadas ficaram zeradas.';
            }

            $vehiclesPayload = [];

            foreach ($vehicleIdsForOutput as $vehicleId) {
                $groups = $this->emptyGroups();

                foreach (($directByVehicleGroup[$vehicleId] ?? []) as $group => $value) {
                    $groups[$group] += $value;
                }

                foreach (($apportionedByVehicleGroup[$vehicleId] ?? []) as $group => $value) {
                    $groups[$group] += $value;
                }

                $metrics = $this->buildMetrics($groups);
                $vehicle = $vehicles->get($vehicleId);
                $basisValue = $weightsByVehicle[$vehicleId] ?? 0;
                $basisPercent = $basisTotal > 0
                    ? round(($basisValue * 100) / $basisTotal, 2)
                    : 0.0;

                $vehiclesPayload[] = [
                    'vehicle_id' => $vehicleId,
                    'plate' => $vehicle?->getAttribute('plate') ?? ('#'.$vehicleId),
                    'type' => $vehicle?->getAttribute('type'),
                    'status' => $vehicle?->getAttribute('status'),
                    'groups_cents' => $groups,
                    'metrics' => $metrics,
                    'apportionment' => [
                        'basis_value' => $basisValue,
                        'basis_percent' => $basisPercent,
                    ],
                ];
            }

            return [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'apportionment' => [
                    'method' => $method,
                    'basis_total' => $basisTotal,
                    'divisor_zero' => $method !== self::METHOD_NONE && $basisTotal === 0,
                    'warnings' => $warnings,
                ],
                'totals' => $this->buildTotals($vehiclesPayload),
                'vehicles' => $vehiclesPayload,
            ];
        });
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function loadDirectByVehicleGroup(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = FinancialEntry::query()
            ->join('financial_categories as category', 'financial_entries.financial_category_id', '=', 'category.id')
            ->selectRaw('financial_entries.vehicle_id as vehicle_id')
            ->selectRaw('category.dre_group as dre_group')
            ->selectRaw(
                'SUM(CASE WHEN financial_entries.type = ? THEN -financial_entries.amount_cents ELSE financial_entries.amount_cents END) as total_cents',
                [FinancialEntryType::Expense->value],
            )
            ->whereBetween('financial_entries.competence_date', [$from->toDateString(), $to->toDateString()])
            ->where('financial_entries.status', '<>', FinancialEntryStatus::Canceled->value)
            ->where('category.allocation', FinancialCategoryAllocation::VehicleDirect->value)
            ->where('category.affects_cashflow', true)
            ->whereNotNull('financial_entries.vehicle_id')
            ->whereNotNull('category.dre_group')
            ->groupBy('financial_entries.vehicle_id', 'category.dre_group')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $vehicleId = (int) $row->vehicle_id;
            $group = (string) $row->dre_group;

            if (! array_key_exists($group, $this->emptyGroups())) {
                continue;
            }

            $result[$vehicleId] ??= [];
            $result[$vehicleId][$group] = (int) $row->total_cents;
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function loadApportionedByGroup(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = FinancialEntry::query()
            ->join('financial_categories as category', 'financial_entries.financial_category_id', '=', 'category.id')
            ->selectRaw('category.dre_group as dre_group')
            ->selectRaw(
                'SUM(CASE WHEN financial_entries.type = ? THEN -financial_entries.amount_cents ELSE financial_entries.amount_cents END) as total_cents',
                [FinancialEntryType::Expense->value],
            )
            ->whereBetween('financial_entries.competence_date', [$from->toDateString(), $to->toDateString()])
            ->where('financial_entries.status', '<>', FinancialEntryStatus::Canceled->value)
            ->where('category.allocation', FinancialCategoryAllocation::Apportioned->value)
            ->where('category.affects_cashflow', true)
            ->whereNotNull('category.dre_group')
            ->groupBy('category.dre_group')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $group = (string) $row->dre_group;

            if (! array_key_exists($group, $this->emptyGroups())) {
                continue;
            }

            $result[$group] = (int) $row->total_cents;
        }

        return $result;
    }

    /**
     * @return EloquentCollection<int, Vehicle>
     */
    private function loadEligibleVehiclesForApportionment(): EloquentCollection
    {
        /** @var EloquentCollection<int, Vehicle> $vehicles */
        $vehicles = Vehicle::query()
            ->whereIn('type', self::ELIGIBLE_VEHICLE_TYPES)
            ->whereNotIn('status', self::INELIGIBLE_VEHICLE_STATUSES)
            ->orderBy('plate')
            ->get(['id', 'plate', 'type', 'status']);

        return $vehicles;
    }

    private function resolveApportionmentMethod(Company $company): string
    {
        $settings = $company->getAttribute('settings');

        if (is_string($settings) && $settings !== '') {
            /** @var mixed $decoded */
            $decoded = json_decode($settings, true);
            $settings = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($settings)) {
            return self::METHOD_BY_KM;
        }

        $method = $settings['dre_apportionment_method'] ?? null;

        if (! is_string($method)) {
            return self::METHOD_BY_KM;
        }

        return in_array($method, self::VALID_METHODS, true)
            ? $method
            : self::METHOD_BY_KM;
    }

    /**
     * @param  EloquentCollection<int, Vehicle>  $eligibleVehicles
     * @param  array<int, array<string, int>>  $directByVehicleGroup
     * @return array<int, int>
     */
    private function resolveWeightsByVehicle(
        string $method,
        EloquentCollection $eligibleVehicles,
        array $directByVehicleGroup,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $vehicleIds = $eligibleVehicles->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        if ($vehicleIds === []) {
            return [];
        }

        if ($method === self::METHOD_NONE) {
            return array_fill_keys($vehicleIds, 0);
        }

        if ($method === self::METHOD_EQUAL) {
            return array_fill_keys($vehicleIds, 1);
        }

        if ($method === self::METHOD_BY_REVENUE) {
            $weights = [];

            foreach ($vehicleIds as $vehicleId) {
                $grossRevenue = $directByVehicleGroup[$vehicleId][FinancialCategoryDreGroup::GrossRevenue->value] ?? 0;
                $weights[$vehicleId] = max($grossRevenue, 0);
            }

            return $weights;
        }

        $kmRows = Fueling::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('fueled_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('km_since_last')
            ->groupBy('vehicle_id')
            ->selectRaw('vehicle_id, SUM(km_since_last) as total_km')
            ->pluck('total_km', 'vehicle_id');

        $weights = [];

        foreach ($vehicleIds as $vehicleId) {
            $weights[$vehicleId] = max((int) ($kmRows[$vehicleId] ?? 0), 0);
        }

        return $weights;
    }

    /**
     * @param  array<string, int>  $apportionedByGroup
     * @param  array<int, int>  $weightsByVehicle
     * @return array<int, array<string, int>>
     */
    private function apportionByVehicleGroup(array $apportionedByGroup, array $weightsByVehicle): array
    {
        if ($weightsByVehicle === [] || $apportionedByGroup === []) {
            return [];
        }

        $vehicleIds = array_keys($weightsByVehicle);
        $weights = array_values($weightsByVehicle);
        $result = [];

        foreach ($apportionedByGroup as $group => $totalCents) {
            $parts = Apportionment::distribute($totalCents, $weights);

            foreach ($parts as $index => $part) {
                $vehicleId = $vehicleIds[$index];
                $result[$vehicleId] ??= [];
                $result[$vehicleId][$group] = ($result[$vehicleId][$group] ?? 0) + $part;
            }
        }

        return $result;
    }

    /**
     * @param  list<int>|null  $requestedVehicleIds
     * @param  array<int, array<string, int>>  $directByVehicleGroup
     * @param  array<int, int>  $weightsByVehicle
     * @return list<int>
     */
    private function resolveVehicleIdsForOutput(?array $requestedVehicleIds, array $directByVehicleGroup, array $weightsByVehicle): array
    {
        $candidateIds = $requestedVehicleIds
            ?? array_values(array_unique([
                ...array_keys($directByVehicleGroup),
                ...array_keys($weightsByVehicle),
            ]));

        if ($candidateIds === []) {
            return [];
        }

        $vehicles = Vehicle::query()
            ->whereIn('id', $candidateIds)
            ->orderBy('plate')
            ->get(['id']);

        return $vehicles->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  list<int>  $vehicleIds
     * @return EloquentCollection<int, Vehicle>
     */
    private function loadVehiclesForOutput(array $vehicleIds): EloquentCollection
    {
        /** @var EloquentCollection<int, Vehicle> $vehicles */
        $vehicles = Vehicle::query()
            ->whereIn('id', $vehicleIds)
            ->get(['id', 'plate', 'type', 'status'])
            ->keyBy('id');

        return $vehicles;
    }

    /**
     * @param  array<string, int>  $groups
     * @return array{
     *     gross_revenue_cents: int,
     *     deductions_cents: int,
     *     net_revenue_cents: int,
     *     variable_cost_cents: int,
     *     contribution_margin_cents: int,
     *     fixed_cost_cents: int,
     *     operational_result_cents: int,
     *     admin_expense_cents: int,
     *     financial_expense_cents: int,
     *     net_result_cents: int
     * }
     */
    private function buildMetrics(array $groups): array
    {
        $grossRevenue = $groups[FinancialCategoryDreGroup::GrossRevenue->value] ?? 0;
        $deductions = $groups[FinancialCategoryDreGroup::Deductions->value] ?? 0;
        $netRevenue = $grossRevenue + $deductions;

        $variableCost = $groups[FinancialCategoryDreGroup::VariableCost->value] ?? 0;
        $contributionMargin = $netRevenue + $variableCost;

        $fixedCost = $groups[FinancialCategoryDreGroup::FixedCost->value] ?? 0;
        $operationalResult = $contributionMargin + $fixedCost;

        $adminExpense = $groups[FinancialCategoryDreGroup::AdminExpense->value] ?? 0;
        $financialExpense = $groups[FinancialCategoryDreGroup::FinancialExpense->value] ?? 0;
        $netResult = $operationalResult + $adminExpense + $financialExpense;

        return [
            'gross_revenue_cents' => $grossRevenue,
            'deductions_cents' => $deductions,
            'net_revenue_cents' => $netRevenue,
            'variable_cost_cents' => $variableCost,
            'contribution_margin_cents' => $contributionMargin,
            'fixed_cost_cents' => $fixedCost,
            'operational_result_cents' => $operationalResult,
            'admin_expense_cents' => $adminExpense,
            'financial_expense_cents' => $financialExpense,
            'net_result_cents' => $netResult,
        ];
    }

    /**
     * @param  list<array{
     *     vehicle_id: int,
     *     plate: string,
     *     type: string|null,
     *     status: string|null,
     *     groups_cents: array<string, int>,
     *     metrics: array<string, int>,
     *     apportionment: array{basis_value: int, basis_percent: float}
     * }>  $vehicles
     * @return array{
     *     vehicles_count: int,
     *     groups_cents: array<string, int>,
     *     gross_revenue_cents: int,
     *     deductions_cents: int,
     *     net_revenue_cents: int,
     *     variable_cost_cents: int,
     *     contribution_margin_cents: int,
     *     fixed_cost_cents: int,
     *     operational_result_cents: int,
     *     admin_expense_cents: int,
     *     financial_expense_cents: int,
     *     net_result_cents: int
     * }
     */
    private function buildTotals(array $vehicles): array
    {
        $groups = $this->emptyGroups();
        $metrics = [
            'gross_revenue_cents' => 0,
            'deductions_cents' => 0,
            'net_revenue_cents' => 0,
            'variable_cost_cents' => 0,
            'contribution_margin_cents' => 0,
            'fixed_cost_cents' => 0,
            'operational_result_cents' => 0,
            'admin_expense_cents' => 0,
            'financial_expense_cents' => 0,
            'net_result_cents' => 0,
        ];

        foreach ($vehicles as $vehicle) {
            foreach ($vehicle['groups_cents'] as $group => $value) {
                $groups[$group] += $value;
            }

            foreach ($metrics as $key => $value) {
                $metrics[$key] = $value + (int) ($vehicle['metrics'][$key] ?? 0);
            }
        }

        return [
            'vehicles_count' => count($vehicles),
            'groups_cents' => $groups,
            ...$metrics,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyGroups(): array
    {
        $groups = [];

        foreach (FinancialCategoryDreGroup::cases() as $group) {
            $groups[$group->value] = 0;
        }

        return $groups;
    }

    /**
     * @param  list<int>|null  $ids
     * @return list<int>|null
     */
    private function normalizeIntegerList(?array $ids, string $field): ?array
    {
        if ($ids === null) {
            return null;
        }

        $result = [];

        foreach ($ids as $id) {
            if (! is_int($id) || $id <= 0) {
                throw ValidationException::withMessages([
                    $field => 'Lista inválida: use apenas IDs inteiros positivos.',
                ]);
            }

            $result[$id] = $id;
        }

        return array_values($result);
    }
}
