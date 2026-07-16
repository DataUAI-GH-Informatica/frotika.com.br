<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Actions\UpdateManualFinancialEntry;
use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UpdateManualFinancialEntryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_atualiza_lancamento_manual_da_empresa_ativa(): void
    {
        $company = $this->createCompany(800);
        [$categoryId, $bankAccountId] = $this->createFinanceBase($company, 'expense');
        $entryId = $this->createManualSettledEntry($company, $categoryId, $bankAccountId);

        $tenant = app(TenantContext::class);
        $tenant->runFor($company, function () use ($bankAccountId): void {
            BankAccount::query()->whereKey($bankAccountId)->update([
                'current_balance_cents' => 777,
            ]);
        });

        $action = app(UpdateManualFinancialEntry::class);

        $action->execute($company, $entryId, [
            'financial_category_id' => $categoryId,
            'bank_account_id' => null,
            'type' => 'expense',
            'description' => 'Despesa ajustada para previsto',
            'competence_date' => '2026-07-25',
            'due_date' => '2026-07-30',
            'paid_at' => null,
            'amount_cents' => 19000,
            'status' => 'forecast',
            'payment_method' => null,
        ]);

        $this->assertDatabaseHas('financial_entries', [
            'id' => $entryId,
            'company_id' => $company->getKey(),
            'description' => 'Despesa ajustada para previsto',
            'competence_date' => '2026-07-25 00:00:00',
            'paid_at' => null,
            'bank_account_id' => null,
            'amount_cents' => 19000,
            'status' => 'forecast',
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'company_id' => $company->getKey(),
            'current_balance_cents' => 0,
        ]);

        $entry = $tenant->runFor($company, fn (): FinancialEntry => FinancialEntry::query()->findOrFail($entryId));

        $this->assertSame(FinancialEntryStatus::Forecast, $entry->status);
    }

    public function test_rejeita_atualizacao_de_lancamento_de_outra_empresa(): void
    {
        $companyA = $this->createCompany(900);
        $companyB = $this->createCompany(901);

        [$categoryIdB, $bankAccountIdB] = $this->createFinanceBase($companyB, 'expense');
        $entryIdFromB = $this->createManualSettledEntry($companyB, $categoryIdB, $bankAccountIdB);

        [$categoryIdA] = $this->createFinanceBase($companyA, 'expense');

        $action = app(UpdateManualFinancialEntry::class);

        $this->expectException(ValidationException::class);

        $action->execute($companyA, $entryIdFromB, [
            'financial_category_id' => $categoryIdA,
            'bank_account_id' => null,
            'type' => 'expense',
            'description' => 'Tentativa invalida',
            'competence_date' => '2026-07-25',
            'due_date' => null,
            'paid_at' => null,
            'amount_cents' => 9900,
            'status' => 'forecast',
            'payment_method' => null,
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createFinanceBase(Company $company, string $categoryType): array
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function () use ($categoryType): array {
            $category = FinancialCategory::query()->create([
                'code' => '9.3',
                'name' => 'Categoria update',
                'type' => $categoryType,
                'dre_group' => 'variable_cost',
                'allocation' => 'vehicle_direct',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 930,
            ]);

            $bankAccount = BankAccount::query()->create([
                'name' => 'Conta update',
                'type' => 'cash',
                'initial_balance_cents' => 0,
                'initial_balance_at' => '2026-07-01',
                'current_balance_cents' => 0,
                'is_default' => true,
                'active' => true,
            ]);

            return [$category->getKey(), $bankAccount->getKey()];
        });
    }

    private function createManualSettledEntry(Company $company, int $categoryId, int $bankAccountId): int
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function () use ($categoryId, $bankAccountId): int {
            $author = User::factory()->create();

            $entry = FinancialEntry::query()->create([
                'financial_category_id' => $categoryId,
                'bank_account_id' => $bankAccountId,
                'type' => 'expense',
                'description' => 'Entrada para update',
                'competence_date' => '2026-07-15',
                'paid_at' => '2026-07-15',
                'amount_cents' => 15000,
                'status' => 'settled',
                'payment_method' => 'pix',
                'created_by' => $author->getKey(),
            ]);

            return $entry->getKey();
        });
    }

    private function createCompany(int $seed): Company
    {
        $owner = User::factory()->create([
            'email' => 'update-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Update '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '77889900'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Update Empresa '.$seed.' LTDA',
            'trade_name' => 'Update Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
