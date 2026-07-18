<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Domain\Billing\Enums\GroupLicenseInvoiceStatus;
use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicense;
use App\Domain\Billing\Models\GroupLicenseInvoice;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Http\Middleware\EnsureGroupLicenseAllowsWrite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EnsureGroupLicenseAllowsWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('billing.lock.write-test')) {
            Route::middleware(['web', 'auth', 'verified', EnsureGroupLicenseAllowsWrite::class])
                ->post('/_test/licencas/escrita', static fn () => response()->json(['ok' => true]))
                ->name('billing.lock.write-test');
        }
    }

    public function test_bloqueia_escrita_quando_licenca_do_grupo_esta_pendente_de_pagamento(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => GroupLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->postJson('/_test/licencas/escrita');

        $response->assertStatus(423);
        $response->assertJsonPath('status', 'group_license_blocked');
    }

    public function test_permite_leitura_do_painel_mesmo_com_licenca_pendente(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => GroupLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_troca_de_empresa_continua_liberada_mesmo_com_bloqueio_de_escrita(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => GroupLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $otherCompany = $this->createCompany($license->group()->firstOrFail(), 'Filial Norte', '55111222000113');
        $user->companies()->attach($otherCompany->getKey());

        $response = $this
            ->actingAs($user)
            ->postJson(route('tenancy.switch-company'), [
                'company_id' => $otherCompany->getKey(),
            ]);

        $response->assertOk();
        $response->assertJsonPath('company_id', $otherCompany->getKey());
    }

    public function test_exibe_banner_persistente_no_dashboard_quando_licenca_esta_pendente(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => GroupLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $this->createPendingInvoice($license);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Operações de escrita estão');
        $response->assertSee('Abrir boleto');
        $response->assertSee('Licença bloqueada');
    }

    public function test_banner_orienta_usuario_nao_owner_a_falar_com_owner(): void
    {
        [$owner, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => GroupLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $this->createPendingInvoice($license);

        $group = $license->group()->firstOrFail();
        $company = $group->primaryCompany()->firstOrFail();

        /** @var User $member */
        $member = User::factory()->create();

        $group->users()->attach($member->getKey(), [
            'role' => 'manager',
            'invited_by' => $owner->getKey(),
            'joined_at' => now(),
        ]);

        $company->users()->attach($member->getKey());

        $member->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $company->getKey(),
        ])->save();

        $response = $this
            ->actingAs($member)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Fale com '.$owner->name.' para regularizar a licença.');
    }

    /**
     * @return array{User, GroupLicense}
     */
    private function createUserWithCurrentCompany(): array
    {
        /** @var User $user */
        $user = User::factory()->create();

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Licença',
            'type' => 'customer',
            'owner_user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        $license = GroupLicense::query()->create([
            'group_id' => $group->getKey(),
            'status' => GroupLicenseStatus::Trialing,
            'trial_starts_at' => now()->subDays(8),
            'trial_ends_at' => now()->subDay(),
            'monthly_price_cents' => 9900,
        ]);

        $company = $this->createCompany($group, 'Empresa Alfa', '55111222000112');

        $group->users()->attach($user->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        $user->companies()->attach($company->getKey());

        $user->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $company->getKey(),
        ])->save();

        return [$user, $license];
    }

    private function createCompany(Group $group, string $name, string $cnpj): Company
    {
        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => $cnpj,
            'legal_name' => $name,
            'trade_name' => $name,
            'tax_regime' => 'simples',
        ]);
    }

    private function createPendingInvoice(GroupLicense $license): GroupLicenseInvoice
    {
        return GroupLicenseInvoice::query()->create([
            'group_license_id' => $license->getKey(),
            'group_id' => $license->group_id,
            'reference_month' => now()->startOfMonth()->toDateString(),
            'amount_cents' => 9900,
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => GroupLicenseInvoiceStatus::Pending,
            'boleto_number' => '34191.79001 01043.510047 91020.150008 9 99990000009900',
            'boleto_url' => 'https://frotika.com.br/boletos/demo',
            'boleto_pdf_url' => 'https://frotika.com.br/boletos/demo.pdf',
        ]);
    }
}
