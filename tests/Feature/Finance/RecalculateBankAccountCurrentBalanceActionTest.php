<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Actions\RecalculateBankAccountCurrentBalance;
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

final class RecalculateBankAccountCurrentBalanceActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalcula_saldo_atual_da_conta_com_base_em_lancamentos_liquidados(): void
    {
        $company = $this->createCompany(1000);

        [$bankAccountId, $expenseCategoryId, $revenueCategoryId] = $this->createFinanceBase($company);

        $this->createEntry($company, $bankAccountId, $expenseCategoryId, 'expense', 'settled', 5000, '2026-07-10');
        $this->createEntry($company, $bankAccountId, $revenueCategoryId, 'revenue', 'settled', 20000, '2026-07-11');
        $this->createEntry($company, $bankAccountId, $expenseCategoryId, 'expense', 'forecast', 9999, null);

        $action = app(RecalculateBankAccountCurrentBalance::class);
        $currentBalance = $action->execute($company, $bankAccountId);

        $this->assertSame(16000, $currentBalance);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'company_id' => $company->getKey(),
            'initial_balance_cents' => 1000,
            'current_balance_cents' => 16000,
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
                'name' => 'Conta saldo',
                'type' => 'cash',
                'initial_balance_cents' => 1000,
                'initial_balance_at' => '2026-07-01',
                'current_balance_cents' => 0,
                'is_default' => true,
                'active' => true,
            ]);

            $expenseCategory = FinancialCategory::query()->create([
                'code' => '9.4',
                'name' => 'Despesa saldo',
                'type' => 'expense',
                'dre_group' => 'variable_cost',
                'allocation' => 'vehicle_direct',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 940,
            ]);

            $revenueCategory = FinancialCategory::query()->create([
                'code' => '9.5',
                'name' => 'Receita saldo',
                'type' => 'revenue',
                'dre_group' => 'gross_revenue',
                'allocation' => 'vehicle_direct',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 950,
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
                'description' => 'Entrada para saldo',
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
            'email' => 'balance-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Balance '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '88990011'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Balance Empresa '.$seed.' LTDA',
            'trade_name' => 'Balance Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
