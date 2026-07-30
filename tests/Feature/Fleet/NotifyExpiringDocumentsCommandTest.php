<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Enums\VehicleOwnership;
use App\Domain\Fleet\Enums\VehicleStatus;
use App\Domain\Fleet\Enums\VehicleType;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Notifications\Fleet\ExpiringDocumentsNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

final class NotifyExpiringDocumentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_notifica_vencimentos_na_janela_e_ignora_datas_folgadas(): void
    {
        Notification::fake();

        [$owner, $company] = $this->createOwnerWithCompany();

        app(TenantContext::class)->runFor($company, function (): void {
            Vehicle::query()->create([
                'plate' => 'AAA1A11',
                'type' => VehicleType::Truck->value,
                'status' => VehicleStatus::Active->value,
                'ownership' => VehicleOwnership::Own->value,
                'crlv_due_at' => Carbon::today()->addDays(20)->toDateString(),
            ]);

            Vehicle::query()->create([
                'plate' => 'BBB2B22',
                'type' => VehicleType::Truck->value,
                'status' => VehicleStatus::Active->value,
                'ownership' => VehicleOwnership::Own->value,
                'crlv_due_at' => Carbon::today()->addDays(80)->toDateString(),
            ]);
        });

        $this->artisan('frotika:notify-expiring-documents')
            ->expectsOutputToContain('Notificacoes de vencimento enviadas')
            ->assertSuccessful();

        Notification::assertSentTo(
            $owner,
            ExpiringDocumentsNotification::class,
            function (ExpiringDocumentsNotification $notification) use ($owner): bool {
                $payload = $notification->toArray($owner);
                $items = collect($payload['items'] ?? []);

                return $items->contains(fn (array $item): bool => ($item['label'] ?? null) === 'CRLV'
                    && ($item['reference'] ?? null) === 'AAA1A11'
                    && ($item['alert'] ?? null) === 'expiring')
                    && ! $items->contains(fn (array $item): bool => ($item['reference'] ?? null) === 'BBB2B22');
            }
        );
    }

    public function test_comando_marca_alerta_como_danger_quando_ha_vencido(): void
    {
        Notification::fake();

        [$owner, $company] = $this->createOwnerWithCompany();

        app(TenantContext::class)->runFor($company, function (): void {
            Vehicle::query()->create([
                'plate' => 'EXP0D00',
                'type' => VehicleType::Truck->value,
                'status' => VehicleStatus::Active->value,
                'ownership' => VehicleOwnership::Own->value,
                'crlv_due_at' => Carbon::today()->subDay()->toDateString(),
            ]);
        });

        $this->artisan('frotika:notify-expiring-documents')->assertSuccessful();

        Notification::assertSentTo(
            $owner,
            ExpiringDocumentsNotification::class,
            function (ExpiringDocumentsNotification $notification) use ($owner): bool {
                $payload = $notification->toArray($owner);
                $items = collect($payload['items'] ?? []);

                return ($payload['level'] ?? null) === 'danger'
                    && $items->contains(fn (array $item): bool => ($item['reference'] ?? null) === 'EXP0D00'
                        && ($item['alert'] ?? null) === 'expired');
            }
        );
    }

    /**
     * @return array{User, Company}
     */
    private function createOwnerWithCompany(): array
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo '.Str::random(5),
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '889991110001'.str_pad((string) random_int(10, 99), 2, '0', STR_PAD_LEFT),
            'legal_name' => 'Transportadora Teste',
            'trade_name' => 'Trans Teste',
            'tax_regime' => 'simples',
        ]);

        $group->forceFill(['primary_company_id' => $company->getKey()])->save();

        $group->users()->attach($owner->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        $owner->companies()->attach($company->getKey());
        $owner->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $company->getKey(),
        ])->save();

        return [$owner, $company];
    }
}
