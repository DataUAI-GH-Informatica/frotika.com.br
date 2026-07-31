<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Actions\SettleFinancialEntry;
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

final class SettleFinancialEntryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_baixa_parcial_com_desconto_e_juros_gera_saldo_previsto(): void
    {
        $company = $this->createCompany(1920);
        [$categoryId, $bankAccountId] = $this->createFinanceBase($company);
        $entryId = $this->createForecastEntry($company, $categoryId, 100000);

        $action = app(SettleFinancialEntry::class);

        $result = $action->execute($company, $entryId, [
            'bank_account_id' => $bankAccountId,
            'paid_at' => '2026-08-20',
            'paid_amount_cents' => 40000,
            'discount_cents' => 5000,
            'interest_cents' => 1000,
            'payment_method' => 'pix',
        ]);

        $this->assertTrue($result['is_partial']);
        $this->assertNotNull($result['remaining_entry_id']);

        $this->assertDatabaseHas('financial_entries', [
            'id' => $entryId,
            'status' => 'settled',
            'amount_cents' => 40000,
            'settlement_discount_cents' => 5000,
            'settlement_interest_cents' => 1000,
            'bank_account_id' => $bankAccountId,
        ]);

        $this->assertDatabaseHas('financial_entries', [
            'id' => $result['remaining_entry_id'],
            'status' => 'forecast',
            'amount_cents' => 56000,
            'bank_account_id' => null,
            'settlement_discount_cents' => 0,
            'settlement_interest_cents' => 0,
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'current_balance_cents' => -40000,
        ]);
    }

    public function test_baixa_integral_sem_valor_informado_liquida_com_juros(): void
    {
        $company = $this->createCompany(1921);
        [$categoryId, $bankAccountId] = $this->createFinanceBase($company);
        $entryId = $this->createForecastEntry($company, $categoryId, 100000);

        $action = app(SettleFinancialEntry::class);

        $result = $action->execute($company, $entryId, [
            'bank_account_id' => $bankAccountId,
            'paid_at' => '2026-08-20',
            'discount_cents' => 0,
            'interest_cents' => 2000,
            'payment_method' => 'pix',
        ]);

        $this->assertFalse($result['is_partial']);

        $this->assertDatabaseHas('financial_entries', [
            'id' => $entryId,
            'status' => 'settled',
            'amount_cents' => 102000,
            'settlement_discount_cents' => 0,
            'settlement_interest_cents' => 2000,
            'bank_account_id' => $bankAccountId,
        ]);

        $tenant = app(TenantContext::class);
        $forecastCount = $tenant->runFor($company, fn (): int => (int) FinancialEntry::query()
            ->where('status', 'forecast')
            ->count());

        $this->assertSame(0, $forecastCount);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccountId,
            'current_balance_cents' => -102000,
        ]);
    }

    public function test_rejeita_valor_pago_maior_que_valor_final(): void
    {
        $company = $this->createCompany(1922);
        [$categoryId, $bankAccountId] = $this->createFinanceBase($company);
        $entryId = $this->createForecastEntry($company, $categoryId, 100000);

        $action = app(SettleFinancialEntry::class);

        $this->expectException(ValidationException::class);

        $action->execute($company, $entryId, [
            'bank_account_id' => $bankAccountId,
            'paid_at' => '2026-08-20',
            'paid_amount_cents' => 100001,
            'discount_cents' => 0,
            'interest_cents' => 0,
            'payment_method' => 'pix',
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createFinanceBase(Company $company): array
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function (): array {
            $category = FinancialCategory::query()->create([
                'code' => '9.8',
                'name' => 'Categoria settle',
                'type' => 'expense',
                'dre_group' => 'variable_cost',
                'allocation' => 'non_vehicle',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 980,
            ]);

            $bankAccount = BankAccount::query()->create([
                'name' => 'Conta settle',
                'type' => 'cash',
                'initial_balance_cents' => 0,
                'initial_balance_at' => '2026-08-01',
                'current_balance_cents' => 0,
                'is_default' => true,
                'active' => true,
            ]);

            return [(int) $category->getKey(), (int) $bankAccount->getKey()];
        });
    }

    private function createForecastEntry(Company $company, int $categoryId, int $amountCents): int
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function () use ($categoryId, $amountCents): int {
            $author = User::factory()->create();

            $entry = FinancialEntry::query()->create([
                'financial_category_id' => $categoryId,
                'type' => 'expense',
                'description' => 'Despesa prevista',
                'competence_date' => '2026-08-10',
                'reference_date' => '2026-08-10',
                'due_date' => '2026-08-10',
                'amount_cents' => $amountCents,
                'settlement_discount_cents' => 0,
                'settlement_interest_cents' => 0,
                'status' => 'forecast',
                'created_by' => $author->getKey(),
            ]);

            return (int) $entry->getKey();
        });
    }

    private function createCompany(int $seed): Company
    {
        $owner = User::factory()->create([
            'email' => 'settle-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Settle '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '99173322'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Settle Empresa '.$seed.' LTDA',
            'trade_name' => 'Settle Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
