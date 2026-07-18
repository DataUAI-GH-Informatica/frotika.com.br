<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Actions\BuildCashFlowMatrix;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowCashFlowController
{
    public function __invoke(Request $request, BuildCashFlowMatrix $action): View|RedirectResponse
    {
        Gate::authorize('viewAny', FinancialEntry::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa para ver o fluxo de caixa.');
        }

        $today = CarbonImmutable::now();
        $from = $request->date('from') ?: $today->startOfMonth();
        $to = $request->date('to') ?: $today->endOfMonth();
        $fromDate = CarbonImmutable::parse($from->format('Y-m-d'));
        $toDate = CarbonImmutable::parse($to->format('Y-m-d'));

        if ($toDate->lt($fromDate)) {
            $toDate = $fromDate;
        }

        $includeForecast = $request->boolean('forecast');
        $accountId = $request->integer('account') ?: null;

        $matrix = $action->execute(
            company: $company,
            fromDate: $fromDate->format('Y-m-d'),
            toDate: $toDate->format('Y-m-d'),
            includeForecast: $includeForecast,
            bankAccountIds: $accountId !== null ? [$accountId] : null,
        );

        return view('cash-flow.index', [
            'matrix' => $matrix,
            'days' => $this->consolidateDays($matrix['accounts'], $matrix['unassigned_forecast']['days']),
            'filters' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
                'forecast' => $includeForecast,
                'account' => $accountId,
            ],
            'accounts' => BankAccount::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Consolida os dias de todas as contas numa única série (soma por data),
     * com o saldo acumulado = soma dos saldos por conta + projeção acumulada
     * dos previstos sem conta-alvo.
     *
     * @param  list<array{days: list<array{date: string, revenue_cents: int, expense_cents: int, net_cents: int, running_balance_cents: int}>}>  $accounts
     * @param  list<array{date: string, revenue_cents: int, expense_cents: int, net_cents: int}>  $unassignedDays
     * @return list<array{date: string, revenue_cents: int, expense_cents: int, net_cents: int, running_balance_cents: int}>
     */
    private function consolidateDays(array $accounts, array $unassignedDays): array
    {
        $accountByDate = [];

        foreach ($accounts as $account) {
            foreach ($account['days'] as $day) {
                $date = $day['date'];
                $accountByDate[$date] ??= [
                    'revenue_cents' => 0,
                    'expense_cents' => 0,
                    'net_cents' => 0,
                    'running_balance_cents' => 0,
                ];
                $accountByDate[$date]['revenue_cents'] += $day['revenue_cents'];
                $accountByDate[$date]['expense_cents'] += $day['expense_cents'];
                $accountByDate[$date]['net_cents'] += $day['net_cents'];
                $accountByDate[$date]['running_balance_cents'] += $day['running_balance_cents'];
            }
        }

        $series = [];
        $cumulativeUnassignedNet = 0;

        foreach ($unassignedDays as $day) {
            $date = $day['date'];
            $account = $accountByDate[$date] ?? [
                'revenue_cents' => 0,
                'expense_cents' => 0,
                'net_cents' => 0,
                'running_balance_cents' => 0,
            ];
            $cumulativeUnassignedNet += $day['net_cents'];

            $series[] = [
                'date' => $date,
                'revenue_cents' => $account['revenue_cents'] + $day['revenue_cents'],
                'expense_cents' => $account['expense_cents'] + $day['expense_cents'],
                'net_cents' => $account['net_cents'] + $day['net_cents'],
                'running_balance_cents' => $account['running_balance_cents'] + $cumulativeUnassignedNet,
            ];
        }

        return $series;
    }
}
