<?php

declare(strict_types=1);

namespace Tests\Feature\Fuelings;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Actions\FuelConsumptionCalculator;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FuelConsumptionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_km_por_litro_so_fecha_entre_dois_tanques_cheios_ignorando_arla(): void
    {
        [$company, $vehicle] = $this->scenario(1);

        $fuelings = app(TenantContext::class)->runFor($company, function () use ($vehicle, $company): array {
            $f1 = $this->fueling($company, $vehicle, ['odometer' => 100000, 'product' => 'diesel_s10', 'liters' => 200, 'full_tank' => true, 'fueled_at' => '2026-07-01 08:00:00']);
            $f2 = $this->fueling($company, $vehicle, ['odometer' => 100500, 'product' => 'diesel_s10', 'liters' => 100, 'full_tank' => false, 'fueled_at' => '2026-07-03 08:00:00']);
            $f3 = $this->fueling($company, $vehicle, ['odometer' => 100600, 'product' => 'arla32', 'liters' => 20, 'full_tank' => false, 'fueled_at' => '2026-07-04 08:00:00']);
            $f4 = $this->fueling($company, $vehicle, ['odometer' => 101000, 'product' => 'diesel_s10', 'liters' => 100, 'full_tank' => true, 'fueled_at' => '2026-07-06 08:00:00']);

            app(FuelConsumptionCalculator::class)->recalculateForVehicle((int) $vehicle->getKey());

            return [
                'f1' => Fueling::query()->find($f1),
                'f2' => Fueling::query()->find($f2),
                'f3' => Fueling::query()->find($f3),
                'f4' => Fueling::query()->find($f4),
            ];
        });

        // Primeiro tanque cheio: sem intervalo anterior, consumo é null (não zero).
        $this->assertNull($fuelings['f1']->km_since_last);
        $this->assertNull($fuelings['f1']->km_per_liter);

        // Tanque parcial não fecha intervalo.
        $this->assertNull($fuelings['f2']->km_per_liter);

        // Arla nunca calcula consumo.
        $this->assertNull($fuelings['f3']->km_since_last);
        $this->assertNull($fuelings['f3']->km_per_liter);

        // Segundo tanque cheio fecha o intervalo: 1000 km / (100 diesel parcial + 100 diesel cheio) = 5 km/l.
        // Arla (20 L) fica de fora.
        $this->assertSame(1000, $fuelings['f4']->km_since_last);
        $this->assertSame('5.000', $fuelings['f4']->km_per_liter);
    }

    public function test_intervalo_nao_carrega_litros_do_tanque_cheio_anterior(): void
    {
        [$company, $vehicle] = $this->scenario(2);

        $f2 = app(TenantContext::class)->runFor($company, function () use ($vehicle, $company): Fueling {
            $this->fueling($company, $vehicle, ['odometer' => 50000, 'product' => 'diesel_s10', 'liters' => 300, 'full_tank' => true, 'fueled_at' => '2026-06-01 08:00:00']);
            $second = $this->fueling($company, $vehicle, ['odometer' => 50600, 'product' => 'diesel_s10', 'liters' => 120, 'full_tank' => true, 'fueled_at' => '2026-06-05 08:00:00']);

            app(FuelConsumptionCalculator::class)->recalculateForVehicle((int) $vehicle->getKey());

            return Fueling::query()->findOrFail($second);
        });

        // 600 km / 120 L (só o próprio tanque cheio) = 5 km/l — os 300 L do primeiro não contam.
        $this->assertSame(600, $f2->km_since_last);
        $this->assertSame('5.000', $f2->km_per_liter);
    }

    public function test_tanques_diferentes_sao_intervalos_independentes(): void
    {
        [$company, $vehicle] = $this->scenario(3);

        $result = app(TenantContext::class)->runFor($company, function () use ($vehicle, $company): array {
            $this->fueling($company, $vehicle, ['odometer' => 10000, 'product' => 'diesel_s10', 'liters' => 100, 'full_tank' => true, 'tank' => 'main', 'fueled_at' => '2026-05-01 08:00:00']);
            $auxFirst = $this->fueling($company, $vehicle, ['odometer' => 10200, 'product' => 'diesel_s10', 'liters' => 80, 'full_tank' => true, 'tank' => 'auxiliary', 'fueled_at' => '2026-05-02 08:00:00']);
            $mainSecond = $this->fueling($company, $vehicle, ['odometer' => 10500, 'product' => 'diesel_s10', 'liters' => 100, 'full_tank' => true, 'tank' => 'main', 'fueled_at' => '2026-05-03 08:00:00']);

            app(FuelConsumptionCalculator::class)->recalculateForVehicle((int) $vehicle->getKey());

            return [
                'aux_first' => Fueling::query()->findOrFail($auxFirst),
                'main_second' => Fueling::query()->findOrFail($mainSecond),
            ];
        });

        // Auxiliar é o primeiro do seu tanque → sem consumo.
        $this->assertNull($result['aux_first']->km_per_liter);

        // Principal fecha entre 10000 e 10500 = 500 km / 100 L = 5 km/l (não contamina com o auxiliar).
        $this->assertSame(500, $result['main_second']->km_since_last);
        $this->assertSame('5.000', $result['main_second']->km_per_liter);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fueling(Company $company, Vehicle $vehicle, array $overrides): int
    {
        $fueling = new Fueling;
        $fueling->forceFill([
            'company_id' => $company->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'fueled_at' => $overrides['fueled_at'],
            'odometer' => $overrides['odometer'],
            'product' => $overrides['product'],
            'liters' => $overrides['liters'],
            'total_cents' => 10000,
            'full_tank' => $overrides['full_tank'],
            'tank' => $overrides['tank'] ?? 'main',
            'payment_method' => 'cash',
        ]);
        // saveQuietly: isola o cálculo de consumo da sincronização financeira.
        $fueling->saveQuietly();

        return (int) $fueling->getKey();
    }

    /**
     * @return array{0: Company, 1: Vehicle}
     */
    private function scenario(int $seed): array
    {
        $owner = User::factory()->create(['email' => 'fuel-calc-'.$seed.'@example.com']);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Fuel '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '55443322'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Fuel Empresa '.$seed.' LTDA',
            'trade_name' => 'Fuel Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);

        $vehicle = app(TenantContext::class)->runFor($company, function () use ($company): Vehicle {
            return Vehicle::query()->create([
                'company_id' => $company->getKey(),
                'plate' => 'FUL'.str_pad((string) 1000, 4, '0', STR_PAD_LEFT),
                'type' => 'tractor',
                'status' => 'active',
                'ownership' => 'own',
                'odometer_initial' => 0,
                'odometer_current' => 0,
            ]);
        });

        return [$company, $vehicle];
    }
}
