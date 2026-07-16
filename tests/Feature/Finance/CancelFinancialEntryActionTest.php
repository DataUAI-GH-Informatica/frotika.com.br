<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Actions\CancelFinancialEntry;
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

final class CancelFinancialEntryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancela_lancamento_manual_da_empresa_ativa(): void
    {
        $company = $this->createCompany(500);
        $entryId = $this->createManualSettledEntry($company);

        $tenant = app(TenantContext::class);
        $bankAccountId = $tenant->runFor($company, fn (): int => (int) FinancialEntry::query()->findOrFail($entryId)->bank_account_id);

        $tenant->runFor($company, function () use ($bankAccountId): void {
            BankAccount::query()->whereKey($bankAccountId)->update([
                'current_balance_cents' => 999,
            ]);
        });

        $action = app(CancelFinancialEntry::class);
        $action->execute($company, $entryId);

        $this->assertDatabaseHas('financial_entries', [
            'id' => $entryId,
            'company_id' => $company->getKey(),
            'status' => 'canceled',
            'paid_at' => null,
            'bank_account_id' => null,
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'company_id' => $company->getKey(),
            'current_balance_cents' => 0,
        ]);

        $entry = $tenant->runFor($company, fn (): FinancialEntry => FinancialEntry::query()->findOrFail($entryId));

        $this->assertSame(FinancialEntryStatus::Canceled, $entry->status);
    }

    public function test_rejeita_cancelamento_de_lancamento_de_outra_empresa(): void
    {
        $companyA = $this->createCompany(600);
        $companyB = $this->createCompany(700);

        $entryIdFromB = $this->createManualSettledEntry($companyB);

        $action = app(CancelFinancialEntry::class);

        $this->expectException(ValidationException::class);

        $action->execute($companyA, $entryIdFromB);

        $this->assertDatabaseHas('financial_entries', [
            'id' => $entryIdFromB,
            'company_id' => $companyB->getKey(),
            'status' => 'settled',
        ]);
    }

    private function createManualSettledEntry(Company $company): int
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function (): int {
            $category = FinancialCategory::query()->create([
                'code' => '9.2',
                'name' => 'Categoria cancelamento',
                'type' => 'expense',
                'dre_group' => 'variable_cost',
                'allocation' => 'vehicle_direct',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 920,
            ]);

            $bankAccount = BankAccount::query()->create([
                'name' => 'Conta cancelamento',
                'type' => 'cash',
                'initial_balance_cents' => 0,
                'initial_balance_at' => '2026-07-01',
                'current_balance_cents' => 0,
                'is_default' => true,
                'active' => true,
            ]);

            $author = User::factory()->create();

            $entry = FinancialEntry::query()->create([
                'financial_category_id' => $category->getKey(),
                'bank_account_id' => $bankAccount->getKey(),
                'type' => 'expense',
                'description' => 'Entrada para cancelamento',
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
            'email' => 'cancel-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Cancel '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '66778899'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Cancel Empresa '.$seed.' LTDA',
            'trade_name' => 'Cancel Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
