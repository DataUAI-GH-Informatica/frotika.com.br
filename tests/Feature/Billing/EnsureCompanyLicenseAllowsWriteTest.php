<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Http\Middleware\EnsureCompanyLicenseAllowsWrite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EnsureCompanyLicenseAllowsWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('billing.lock.write-test')) {
            Route::middleware(['web', 'auth', 'verified', EnsureCompanyLicenseAllowsWrite::class])
                ->post('/_test/licencas/escrita', static fn () => response()->json(['ok' => true]))
                ->name('billing.lock.write-test');
        }
    }

    public function test_bloqueia_escrita_quando_licenca_da_empresa_esta_pendente_de_pagamento(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->postJson('/_test/licencas/escrita');

        $response->assertStatus(423);
        $response->assertJsonPath('status', 'company_license_blocked');
    }

    public function test_permite_leitura_da_tela_de_assinatura_mesmo_com_licenca_pendente(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->get(route('billing.licenses.index'));

        $response->assertOk();
    }

    public function test_troca_de_empresa_continua_liberada_mesmo_com_bloqueio_de_escrita(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
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

    public function test_seletor_exibe_marcador_quando_status_da_empresa_difere_da_atual(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $otherCompany = $this->createCompany($license->group()->firstOrFail(), 'Filial Norte', '55111222000113');
        $user->companies()->attach($otherCompany->getKey());

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Filial Norte [Trial]');
    }

    public function test_exibe_banner_persistente_no_dashboard_quando_licenca_esta_pendente(): void
    {
        [$user, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $this->createPendingInvoice($license);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Operações de escrita estão bloqueadas.');
        $response->assertSee('Ver assinatura');
        $response->assertSee('Licença bloqueada');
    }

    public function test_banner_orienta_usuario_nao_owner_a_falar_com_owner(): void
    {
        [$owner, $license] = $this->createUserWithCurrentCompany();

        $license->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
            'trial_ends_at' => now()->subDay(),
        ])->save();

        $this->createPendingInvoice($license);

        $group = $license->group()->firstOrFail();
        $company = $license->company()->firstOrFail();

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
     * @return array{User, CompanyLicense}
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

        $license = CompanyLicense::query()
            ->where('company_id', $company->getKey())
            ->firstOrFail();

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

    private function createPendingInvoice(CompanyLicense $license): CompanyLicenseInvoice
    {
        return CompanyLicenseInvoice::query()->create([
            'company_license_id' => $license->getKey(),
            'group_id' => $license->group_id,
            'company_id' => $license->company_id,
            'reference_month' => now()->startOfMonth()->toDateString(),
            'amount_cents' => 9900,
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => CompanyLicenseInvoiceStatus::Pending,
            'boleto_number' => '34191.79001 01043.510047 91020.150008 9 99990000009900',
            'boleto_url' => 'https://frotika.com.br/boletos/demo',
            'boleto_pdf_url' => 'https://frotika.com.br/boletos/demo.pdf',
        ]);
    }
}
