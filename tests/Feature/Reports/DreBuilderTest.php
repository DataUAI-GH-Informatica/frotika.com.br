<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Reports\Dre\DreBuilder;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DreBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_rateia_despesa_por_maior_resto_quando_metodo_igual(): void
    {
        $company = $this->createCompany(901);
        $author = User::factory()->create();

        $this->setApportionmentMethod($company, 'equal');

        $tenant = app(TenantContext::class);

        [$vehicleAId, $vehicleBId, $vehicleCId, $revenueCategoryId, $adminCategoryId] = $tenant->runFor(
            $company,
            function () use ($company): array {
                app(SeedDefaultFinancialCategories::class)->execute($company);

                $vehicleA = Vehicle::query()->create([
                    'plate' => 'QAA1A11',
                    'type' => 'tractor',
                    'status' => 'active',
                    'ownership' => 'own',
                ]);

                $vehicleB = Vehicle::query()->create([
                    'plate' => 'QBB2B22',
                    'type' => 'truck',
                    'status' => 'active',
                    'ownership' => 'own',
                ]);

                $vehicleC = Vehicle::query()->create([
                    'plate' => 'QCC3C33',
                    'type' => 'toco',
                    'status' => 'active',
                    'ownership' => 'own',
                ]);

                $revenueCategory = FinancialCategory::query()->where('code', '1.1')->firstOrFail();
                $adminCategory = FinancialCategory::query()->where('code', '5.1')->firstOrFail();

                return [
                    (int) $vehicleA->getKey(),
                    (int) $vehicleB->getKey(),
                    (int) $vehicleC->getKey(),
                    (int) $revenueCategory->getKey(),
                    (int) $adminCategory->getKey(),
                ];
            }
        );

        $this->createEntry($company, $author, $vehicleAId, $revenueCategoryId, 'revenue', 100_000, '2026-07-10');
        $this->createEntry($company, $author, $vehicleBId, $revenueCategoryId, 'revenue', 100_000, '2026-07-10');
        $this->createEntry($company, $author, $vehicleCId, $revenueCategoryId, 'revenue', 100_000, '2026-07-10');

        $this->createEntry($company, $author, null, $adminCategoryId, 'expense', 100_000, '2026-07-10');

        $dre = app(DreBuilder::class)->execute($company, '2026-07-01', '2026-07-31');

        $this->assertSame('equal', $dre['apportionment']['method']);
        $this->assertFalse($dre['apportionment']['divisor_zero']);
        $this->assertSame(3, $dre['totals']['vehicles_count']);

        $adminByVehicle = [];

        foreach ($dre['vehicles'] as $vehicle) {
            $adminByVehicle[$vehicle['vehicle_id']] = $vehicle['groups_cents']['admin_expense'];
        }

        $this->assertSame(-100_000, array_sum($adminByVehicle));
        $this->assertSame(-33_333, $adminByVehicle[$vehicleAId]);
        $this->assertSame(-33_333, $adminByVehicle[$vehicleBId]);
        $this->assertSame(-33_334, $adminByVehicle[$vehicleCId]);
    }

    public function test_rateio_por_km_com_divisor_zero_retorna_rateio_zero_e_aviso(): void
    {
        $company = $this->createCompany(902);
        $author = User::factory()->create();

        $this->setApportionmentMethod($company, 'by_km');

        $tenant = app(TenantContext::class);

        [$vehicleAId, $vehicleBId, $adminCategoryId] = $tenant->runFor(
            $company,
            function () use ($company): array {
                app(SeedDefaultFinancialCategories::class)->execute($company);

                $vehicleA = Vehicle::query()->create([
                    'plate' => 'QDD4D44',
                    'type' => 'tractor',
                    'status' => 'active',
                    'ownership' => 'own',
                ]);

                $vehicleB = Vehicle::query()->create([
                    'plate' => 'QEE5E55',
                    'type' => 'truck',
                    'status' => 'active',
                    'ownership' => 'own',
                ]);

                $adminCategory = FinancialCategory::query()->where('code', '5.1')->firstOrFail();

                return [
                    (int) $vehicleA->getKey(),
                    (int) $vehicleB->getKey(),
                    (int) $adminCategory->getKey(),
                ];
            }
        );

        $this->createEntry($company, $author, null, $adminCategoryId, 'expense', 20_000, '2026-07-15');

        $dre = app(DreBuilder::class)->execute($company, '2026-07-01', '2026-07-31');

        $this->assertSame('by_km', $dre['apportionment']['method']);
        $this->assertTrue($dre['apportionment']['divisor_zero']);
        $this->assertNotEmpty($dre['apportionment']['warnings']);

        $adminByVehicle = [];

        foreach ($dre['vehicles'] as $vehicle) {
            $adminByVehicle[$vehicle['vehicle_id']] = $vehicle['groups_cents']['admin_expense'];
        }

        $this->assertSame(0, $adminByVehicle[$vehicleAId]);
        $this->assertSame(0, $adminByVehicle[$vehicleBId]);
        $this->assertSame(0, array_sum($adminByVehicle));
    }

    public function test_ignora_categoria_sem_efeito_de_caixa_no_dre(): void
    {
        $company = $this->createCompany(904);
        $author = User::factory()->create();

        $this->setApportionmentMethod($company, 'equal');

        $tenant = app(TenantContext::class);

        [$vehicleId, $nonCashAdminCategoryId] = $tenant->runFor(
            $company,
            function () use ($company): array {
                app(SeedDefaultFinancialCategories::class)->execute($company);

                $vehicle = Vehicle::query()->create([
                    'plate' => 'QFF6F66',
                    'type' => 'tractor',
                    'status' => 'active',
                    'ownership' => 'own',
                ]);

                $category = FinancialCategory::query()->create([
                    'code' => '9.5',
                    'name' => 'Ajuste sem caixa',
                    'type' => 'expense',
                    'dre_group' => 'admin_expense',
                    'allocation' => 'apportioned',
                    'affects_cashflow' => false,
                    'is_system' => false,
                    'active' => true,
                    'sort_order' => 950,
                ]);

                return [(int) $vehicle->getKey(), (int) $category->getKey()];
            }
        );

        $this->createEntry($company, $author, null, $nonCashAdminCategoryId, 'expense', 50_000, '2026-07-20');

        $dre = app(DreBuilder::class)->execute($company, '2026-07-01', '2026-07-31');

        $this->assertSame(0, $dre['totals']['admin_expense_cents']);
        $this->assertSame(0, $dre['vehicles'][0]['groups_cents']['admin_expense']);
        $this->assertSame($vehicleId, $dre['vehicles'][0]['vehicle_id']);
    }

    public function test_periodo_sem_dados_retorna_zerado_sem_erro(): void
    {
        $company = $this->createCompany(903);

        $dre = app(DreBuilder::class)->execute($company, '2026-07-01', '2026-07-31');

        $this->assertSame([], $dre['vehicles']);
        $this->assertSame(0, $dre['totals']['vehicles_count']);
        $this->assertSame(0, $dre['totals']['net_result_cents']);
        $this->assertSame(0, $dre['totals']['gross_revenue_cents']);
        $this->assertSame(0, $dre['totals']['admin_expense_cents']);
    }

    private function createEntry(
        Company $company,
        User $author,
        ?int $vehicleId,
        int $categoryId,
        string $type,
        int $amountCents,
        string $competenceDate,
    ): void {
        $tenant = app(TenantContext::class);

        $tenant->runFor($company, function () use ($author, $vehicleId, $categoryId, $type, $amountCents, $competenceDate): void {
            FinancialEntry::query()->create([
                'financial_category_id' => $categoryId,
                'bank_account_id' => null,
                'vehicle_id' => $vehicleId,
                'driver_id' => null,
                'trip_id' => null,
                'type' => $type,
                'description' => 'Lançamento para DRE',
                'document_number' => null,
                'competence_date' => $competenceDate,
                'due_date' => null,
                'paid_at' => null,
                'amount_cents' => $amountCents,
                'status' => 'forecast',
                'payment_method' => null,
                'recurrence_id' => null,
                'created_by' => $author->getKey(),
            ]);
        });
    }

    private function setApportionmentMethod(Company $company, string $method): void
    {
        $company->forceFill([
            'settings' => json_encode([
                'dre_apportionment_method' => $method,
            ], JSON_THROW_ON_ERROR),
        ])->save();
    }

    private function createCompany(int $seed): Company
    {
        $owner = User::factory()->create([
            'email' => 'dre-owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo DRE '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '99887766'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'DRE Empresa '.$seed.' LTDA',
            'trade_name' => 'DRE Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
