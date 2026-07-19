<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DreScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparativo_da_frota_renderiza_com_os_veiculos(): void
    {
        [$owner, $company, $vehicleId] = $this->scenario(1);

        $response = $this->actingAs($owner)->get(route('dre.index', [
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertViewIs('dre.index');
        $response->assertViewHas('mode', 'comparative');
        $response->assertSee('DRE veicular');
        $response->assertViewHas('rows', function (array $rows) use ($vehicleId): bool {
            return count($rows) === 1 && $rows[0]['vehicle_id'] === $vehicleId;
        });
    }

    public function test_dre_individual_renderiza_com_as_linhas_do_resultado(): void
    {
        [$owner, $company, $vehicleId] = $this->scenario(2);

        $response = $this->actingAs($owner)->get(route('dre.index', [
            'from' => '2026-07-01',
            'to' => '2026-07-31',
            'vehicle' => $vehicleId,
        ]));

        $response->assertOk();
        $response->assertViewHas('mode', 'individual');
        $response->assertSee('RESULTADO LÍQUIDO DO VEÍCULO');
        $response->assertViewHas('vehicle', function (array $vehicle) use ($vehicleId): bool {
            return $vehicle['vehicle_id'] === $vehicleId
                && $vehicle['metrics']['gross_revenue_cents'] === 100_000;
        });
    }

    public function test_veiculo_inexistente_retorna_404(): void
    {
        [$owner] = $this->scenario(3);

        $this->actingAs($owner)
            ->get(route('dre.index', ['vehicle' => 999999]))
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Company, 2: int}
     */
    private function scenario(int $seed): array
    {
        $owner = User::factory()->create(['email' => 'dre-screen-'.$seed.'@example.com']);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo DRE Tela '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '33445566'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'DRE Tela '.$seed.' LTDA',
            'trade_name' => 'DRE Tela '.$seed,
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

        $vehicleId = app(TenantContext::class)->runFor($company, function () use ($company): int {
            app(SeedDefaultFinancialCategories::class)->execute($company);

            $vehicle = Vehicle::query()->create([
                'plate' => 'QHH8H88',
                'type' => 'tractor',
                'status' => 'active',
                'ownership' => 'own',
            ]);

            $revenue = FinancialCategory::query()->where('code', '1.1')->firstOrFail();

            FinancialEntry::query()->create([
                'financial_category_id' => $revenue->getKey(),
                'vehicle_id' => $vehicle->getKey(),
                'type' => 'revenue',
                'description' => 'Frete',
                'competence_date' => '2026-07-10',
                'amount_cents' => 100_000,
                'status' => 'forecast',
                'created_by' => 1,
            ]);

            return (int) $vehicle->getKey();
        });

        return [$owner, $company, $vehicleId];
    }
}
