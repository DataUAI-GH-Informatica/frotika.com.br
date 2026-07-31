<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Actions\CreateScheduledFinancialEntries;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CreateScheduledFinancialEntriesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_lancamento_mensal_recorrente_com_data_de_referencia(): void
    {
        $company = $this->createCompany(1910);
        $author = User::factory()->create();
        $categoryId = $this->createCategory($company, 'expense');

        $action = app(CreateScheduledFinancialEntries::class);

        $result = $action->execute($company, (int) $author->getKey(), [
            'launch_mode' => 'monthly',
            'financial_category_id' => $categoryId,
            'type' => 'expense',
            'description' => 'Energia do escritorio',
            'amount_cents' => 25000,
            'reference_date' => '2026-08-15',
            'competence_date' => '2026-08-15',
            'status' => 'forecast',
        ]);

        $this->assertSame(1, $result['entries_created']);

        $this->assertDatabaseHas('recurrences', [
            'id' => $result['recurrence_id'],
            'kind' => 'recurring',
            'frequency' => 'monthly',
            'day_of_month' => 15,
        ]);

        $this->assertDatabaseHas('financial_entries', [
            'recurrence_id' => $result['recurrence_id'],
            'competence_date' => '2026-08-15 00:00:00',
            'reference_date' => '2026-08-15 00:00:00',
            'status' => 'forecast',
            'installment_number' => null,
            'installment_total' => null,
        ]);
    }

    public function test_cria_parcelamento_dividindo_valor_total_em_centavos(): void
    {
        $company = $this->createCompany(1911);
        $author = User::factory()->create();
        $categoryId = $this->createCategory($company, 'expense');

        $action = app(CreateScheduledFinancialEntries::class);

        $result = $action->execute($company, (int) $author->getKey(), [
            'launch_mode' => 'installment',
            'financial_category_id' => $categoryId,
            'type' => 'expense',
            'description' => 'Compra parcelada',
            'amount_cents' => 100000,
            'reference_date' => '2026-08-31',
            'competence_date' => '2026-08-10',
            'installments' => 3,
            'status' => 'forecast',
        ]);

        $this->assertSame(3, $result['entries_created']);

        $tenant = app(TenantContext::class);

        $entries = $tenant->runFor($company, fn () => FinancialEntry::query()
            ->where('recurrence_id', $result['recurrence_id'])
            ->orderBy('installment_number')
            ->get([
                'amount_cents',
                'competence_date',
                'reference_date',
                'installment_number',
                'installment_total',
            ]));

        $this->assertCount(3, $entries);
        $this->assertSame([33333, 33333, 33334], $entries->pluck('amount_cents')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(100000, $entries->sum('amount_cents'));

        $this->assertSame('2026-08-10', $entries[0]->competence_date->toDateString());
        $this->assertSame('2026-08-10', $entries[1]->competence_date->toDateString());
        $this->assertSame('2026-08-10', $entries[2]->competence_date->toDateString());

        $this->assertSame('2026-08-31', $entries[0]->reference_date->toDateString());
        $this->assertSame('2026-09-30', $entries[1]->reference_date->toDateString());
        $this->assertSame('2026-10-31', $entries[2]->reference_date->toDateString());

        $this->assertDatabaseHas('recurrences', [
            'id' => $result['recurrence_id'],
            'kind' => 'installment',
            'installments' => 3,
            'installments_generated' => 3,
            'fixed_competence_date' => '2026-08-10 00:00:00',
        ]);
    }

    private function createCategory(Company $company, string $type): int
    {
        $tenant = app(TenantContext::class);

        return $tenant->runFor($company, function () use ($type): int {
            $category = FinancialCategory::query()->create([
                'code' => '11.8',
                'name' => 'Categoria agendada',
                'type' => $type,
                'dre_group' => 'fixed_cost',
                'allocation' => 'non_vehicle',
                'affects_cashflow' => true,
                'is_system' => false,
                'active' => true,
                'sort_order' => 1180,
            ]);

            return (int) $category->getKey();
        });
    }

    private function createCompany(int $seed): Company
    {
        $owner = User::factory()->create([
            'email' => 'scheduled-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Scheduled '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '55173322'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Scheduled Empresa '.$seed.' LTDA',
            'trade_name' => 'Scheduled Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
