<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CashFlowScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_fluxo_de_caixa_consolida_realizado_no_periodo(): void
    {
        [$owner, $company, $accountId, $revenueCat, $expenseCat] = $this->scenario(1);
        $this->settledEntry($company, $owner, $accountId, $revenueCat, 'revenue', 500000, '2026-07-05');
        $this->settledEntry($company, $owner, $accountId, $expenseCat, 'expense', 120000, '2026-07-10');

        $response = $this->actingAs($owner)->get(route('cash-flow.index', [
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertViewHas('matrix', function (array $matrix): bool {
            return $matrix['totals']['revenue_cents'] === 500000
                && $matrix['totals']['expense_cents'] === 120000
                && $matrix['totals']['net_cents'] === 380000
                && $matrix['totals']['closing_balance_cents'] === 380000
                && $matrix['include_forecast'] === false;
        });
    }

    public function test_previsto_com_conta_alvo_so_projeta_com_o_toggle(): void
    {
        [$owner, $company, $accountId, $revenueCat] = $this->scenario(2);
        $this->forecastEntry($company, $owner, $accountId, $revenueCat, 'revenue', 300000, '2026-07-15');

        $without = $this->actingAs($owner)->get(route('cash-flow.index', ['from' => '2026-07-01', 'to' => '2026-07-31']));
        $without->assertViewHas('matrix', fn (array $m): bool => $m['totals']['revenue_cents'] === 0);

        $with = $this->actingAs($owner)->get(route('cash-flow.index', ['from' => '2026-07-01', 'to' => '2026-07-31', 'forecast' => '1']));
        $with->assertViewHas('matrix', fn (array $m): bool => $m['totals']['revenue_cents'] === 300000 && $m['include_forecast'] === true);
    }

    public function test_previsto_sem_conta_projeta_no_consolidado_com_o_toggle(): void
    {
        [$owner, $company, $accountId, $revenueCat] = $this->scenario(3);
        // Saldo realizado inicial na conta.
        $this->settledEntry($company, $owner, $accountId, $revenueCat, 'revenue', 100000, '2026-07-02');
        // Previsto sem conta-alvo.
        $this->forecastEntry($company, $owner, null, $revenueCat, 'revenue', 250000, '2026-07-20');

        $without = $this->actingAs($owner)->get(route('cash-flow.index', ['from' => '2026-07-01', 'to' => '2026-07-31']));
        $without->assertViewHas('matrix', fn (array $m): bool => $m['totals']['closing_balance_cents'] === 100000);

        $with = $this->actingAs($owner)->get(route('cash-flow.index', ['from' => '2026-07-01', 'to' => '2026-07-31', 'forecast' => '1']));
        $with->assertViewHas('matrix', function (array $m): bool {
            return $m['totals']['revenue_cents'] === 350000
                && $m['totals']['closing_balance_cents'] === 350000
                && $m['unassigned_forecast']['revenue_cents'] === 250000;
        });
        $with->assertViewHas('days', function (array $days): bool {
            $last = end($days);

            return $last['running_balance_cents'] === 350000;
        });
    }

    /**
     * @return array{0: User, 1: Company, 2: int, 3: int, 4: int}
     */
    private function scenario(int $seed): array
    {
        $owner = User::factory()->create(['email' => 'cash-owner-'.$seed.'@example.com']);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Cash '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '44556677'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Cash Empresa '.$seed.' LTDA',
            'trade_name' => 'Cash Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);

        $owner->groups()->attach($group->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
        $owner->companies()->attach($company->getKey());
        $owner->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $company->getKey(),
        ])->save();

        [$accountId, $revenueCat, $expenseCat] = app(TenantContext::class)->runFor($company, function (): array {
            $account = BankAccount::query()->create([
                'name' => 'Caixa', 'type' => 'cash', 'initial_balance_cents' => 0,
                'current_balance_cents' => 0, 'is_default' => true, 'active' => true,
            ]);
            $revenue = FinancialCategory::query()->create([
                'code' => '1.1', 'name' => 'Receita de fretes', 'type' => 'revenue',
                'dre_group' => 'gross_revenue', 'allocation' => 'vehicle_direct',
                'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 110,
            ]);
            $expense = FinancialCategory::query()->create([
                'code' => '3.1', 'name' => 'Combustível', 'type' => 'expense',
                'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct',
                'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 310,
            ]);

            return [(int) $account->getKey(), (int) $revenue->getKey(), (int) $expense->getKey()];
        });

        return [$owner, $company, $accountId, $revenueCat, $expenseCat];
    }

    private function settledEntry(Company $company, User $author, int $accountId, int $categoryId, string $type, int $cents, string $paidAt): void
    {
        app(TenantContext::class)->runFor($company, function () use ($company, $author, $accountId, $categoryId, $type, $cents, $paidAt): void {
            FinancialEntry::query()->create([
                'company_id' => $company->getKey(),
                'bank_account_id' => $accountId,
                'financial_category_id' => $categoryId,
                'type' => $type,
                'description' => 'Movimento',
                'competence_date' => $paidAt,
                'paid_at' => $paidAt,
                'amount_cents' => $cents,
                'status' => 'settled',
                'created_by' => $author->getKey(),
            ]);
        });
    }

    private function forecastEntry(Company $company, User $author, ?int $accountId, int $categoryId, string $type, int $cents, string $dueDate): void
    {
        app(TenantContext::class)->runFor($company, function () use ($company, $author, $accountId, $categoryId, $type, $cents, $dueDate): void {
            FinancialEntry::query()->create([
                'company_id' => $company->getKey(),
                'bank_account_id' => $accountId,
                'financial_category_id' => $categoryId,
                'type' => $type,
                'description' => 'Previsto',
                'competence_date' => $dueDate,
                'due_date' => $dueDate,
                'amount_cents' => $cents,
                'status' => 'forecast',
                'created_by' => $author->getKey(),
            ]);
        });
    }
}
