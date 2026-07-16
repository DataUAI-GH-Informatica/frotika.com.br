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

final class RecalculateBalancesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_recalcula_saldos_e_atualiza_contas(): void
    {
        $company = $this->createCompany(1100);
        [$bankAccountId, $expenseCategoryId, $revenueCategoryId] = $this->createFinanceBase($company);

        $this->createEntry($company, $bankAccountId, $expenseCategoryId, 'expense', 'settled', 3000, '2026-07-10');
        $this->createEntry($company, $bankAccountId, $revenueCategoryId, 'revenue', 'settled', 10000, '2026-07-11');

        $this->artisan('frotika:recalculate-balances')
            ->expectsOutputToContain('Reconciliacao concluida (apply)')
            ->assertSuccessful();

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'company_id' => $company->getKey(),
            'current_balance_cents' => 8000,
        ]);
    }

    public function test_comando_dry_run_nao_persiste_saldo_recalculado(): void
    {
        $company = $this->createCompany(1200);
        [$bankAccountId, $expenseCategoryId, $revenueCategoryId] = $this->createFinanceBase($company);

        $this->createEntry($company, $bankAccountId, $expenseCategoryId, 'expense', 'settled', 3000, '2026-07-10');
        $this->createEntry($company, $bankAccountId, $revenueCategoryId, 'revenue', 'settled', 10000, '2026-07-11');

        $this->artisan('frotika:recalculate-balances --dry-run')
            ->expectsOutputToContain('Reconciliacao concluida (dry-run)')
            ->assertSuccessful();

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'company_id' => $company->getKey(),
            'current_balance_cents' => 0,
        ]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function createFinanceBase(Company $company): array
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function (): array {
            $bankAccount = BankAccount::query()->create([
                'name' => 'Conta comando',
                'type' => 'cash',
                'initial_balance_cents' => 1000,
                'initial_balance_at' => '2026-07-01',
                'current_balance_cents' => 0,
                'is_default' => true,
                'active' => true,
            ]);

            $expenseCategory = FinancialCategory::query()->create([
                'code' => '9.6',
                'name' => 'Despesa comando',
                'type' => 'expense',
                'dre_group' => 'variable_cost',
                'allocation' => 'vehicle_direct',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 960,
            ]);

            $revenueCategory = FinancialCategory::query()->create([
                'code' => '9.7',
                'name' => 'Receita comando',
                'type' => 'revenue',
                'dre_group' => 'gross_revenue',
                'allocation' => 'vehicle_direct',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 970,
            ]);

            return [$bankAccount->getKey(), $expenseCategory->getKey(), $revenueCategory->getKey()];
        });
    }

    private function createEntry(
        Company $company,
        int $bankAccountId,
        int $categoryId,
        string $type,
        string $status,
        int $amountCents,
        ?string $paidAt,
    ): void {
        $tenant = app(TenantContext::class);

        $tenant->runFor($company, function () use ($bankAccountId, $categoryId, $type, $status, $amountCents, $paidAt): void {
            $author = User::factory()->create();

            FinancialEntry::query()->create([
                'financial_category_id' => $categoryId,
                'bank_account_id' => $bankAccountId,
                'type' => $type,
                'description' => 'Entrada para comando',
                'competence_date' => '2026-07-15',
                'paid_at' => $paidAt,
                'amount_cents' => $amountCents,
                'status' => $status,
                'payment_method' => $paidAt !== null ? 'pix' : null,
                'created_by' => $author->getKey(),
            ]);
        });
    }

    private function createCompany(int $seed): Company
    {
        $owner = User::factory()->create([
            'email' => 'command-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Command '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '99001122'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Command Empresa '.$seed.' LTDA',
            'trade_name' => 'Command Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
