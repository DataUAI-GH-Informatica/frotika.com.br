<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Domain\Tenancy\Enums\GroupType;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PlatformBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_da_plataforma_lista_grupos_cadastrados(): void
    {
        $admin = $this->createPlatformAdmin();
        [$group] = $this->createCustomerScenario();

        $response = $this
            ->actingAs($admin)
            ->get(route('platform.groups.index'));

        $response->assertOk();
        $response->assertSee($group->name);
    }

    public function test_admin_da_plataforma_ve_detalhe_do_grupo(): void
    {
        $admin = $this->createPlatformAdmin();
        [$group, $company] = $this->createCustomerScenario();

        $response = $this
            ->actingAs($admin)
            ->get(route('platform.groups.show', ['group' => $group->getKey()]));

        $response->assertOk();
        $response->assertSee($company->trade_name);
        $response->assertSee('Lançar boleto');
    }

    public function test_usuario_comum_nao_acessa_o_painel_da_plataforma(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);

        $response = $this
            ->actingAs($user)
            ->get(route('platform.groups.index'));

        $response->assertForbidden();
    }

    public function test_admin_da_plataforma_lanca_boleto_manual_para_licenca_de_um_cliente(): void
    {
        $admin = $this->createPlatformAdmin();
        [$group, $company, $license] = $this->createCustomerScenario();

        $response = $this
            ->actingAs($admin)
            ->post(route('platform.licenses.issue', ['license' => $license->getKey()]), [
                'amount_cents' => 12990,
                'due_date' => now()->addDays(3)->toDateString(),
                'reference_month' => now()->format('Y-m'),
                'boleto_number' => '34191.79001 01043.510047 91020.150008 9 99990000012990',
                'boleto_url' => 'https://pagamentos.exemplo.com/boleto/12990',
                'boleto_pdf_url' => 'https://pagamentos.exemplo.com/boleto/12990.pdf',
            ]);

        $response->assertRedirect(route('platform.groups.show', ['group' => $group->getKey()]));
        $response->assertSessionHas('status', 'Boleto lançado com sucesso para a empresa selecionada.');

        $this->assertDatabaseHas('company_license_invoices', [
            'company_license_id' => $license->getKey(),
            'group_id' => $group->getKey(),
            'company_id' => $company->getKey(),
            'amount_cents' => 12990,
            'status' => CompanyLicenseInvoiceStatus::Pending->value,
            'created_by_user_id' => $admin->getKey(),
        ]);

        $this->assertDatabaseHas('company_licenses', [
            'id' => $license->getKey(),
            'status' => CompanyLicenseStatus::PendingPayment->value,
        ]);
    }

    public function test_emissao_de_boleto_e_bloqueada_antes_do_fim_do_trial(): void
    {
        $admin = $this->createPlatformAdmin();
        [$group, , $license] = $this->createCustomerScenario(trialEnded: false);

        $response = $this
            ->actingAs($admin)
            ->from(route('platform.groups.show', ['group' => $group->getKey()]))
            ->post(route('platform.licenses.issue', ['license' => $license->getKey()]), [
                'amount_cents' => 9900,
                'due_date' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertRedirect(route('platform.groups.show', ['group' => $group->getKey()]));
        $response->assertSessionHasErrors(['due_date']);

        $this->assertDatabaseCount('company_license_invoices', 0);
    }

    public function test_admin_da_plataforma_da_baixa_manual_e_ativa_a_licenca(): void
    {
        $admin = $this->createPlatformAdmin();
        [$group, $company, $license] = $this->createCustomerScenario();

        $invoice = CompanyLicenseInvoice::query()->create([
            'company_license_id' => $license->getKey(),
            'group_id' => $group->getKey(),
            'company_id' => $company->getKey(),
            'reference_month' => now()->startOfMonth()->toDateString(),
            'amount_cents' => 15990,
            'due_date' => now()->addDays(1)->toDateString(),
            'status' => CompanyLicenseInvoiceStatus::Pending,
            'boleto_url' => 'https://pagamentos.exemplo.com/boleto/15990',
        ]);

        $license->forceFill(['status' => CompanyLicenseStatus::PendingPayment])->save();

        $response = $this
            ->actingAs($admin)
            ->post(route('platform.invoices.mark-paid', ['invoice' => $invoice->getKey()]), [
                'paid_at' => now()->toDateString(),
                'paid_note' => 'Conferido no banco manualmente',
            ]);

        $response->assertRedirect(route('platform.groups.show', ['group' => $group->getKey()]));
        $response->assertSessionHas('status', 'Pagamento confirmado manualmente com sucesso.');

        $this->assertDatabaseHas('company_license_invoices', [
            'id' => $invoice->getKey(),
            'status' => CompanyLicenseInvoiceStatus::Paid->value,
            'paid_note' => 'Conferido no banco manualmente',
            'confirmed_by_user_id' => $admin->getKey(),
        ]);

        $this->assertDatabaseHas('company_licenses', [
            'id' => $license->getKey(),
            'status' => CompanyLicenseStatus::Active->value,
        ]);

        $this->assertNotNull($license->fresh()->activated_at);
    }

    public function test_usuario_comum_nao_consegue_lancar_boleto(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);
        [, , $license] = $this->createCustomerScenario();

        $response = $this
            ->actingAs($user)
            ->post(route('platform.licenses.issue', ['license' => $license->getKey()]), [
                'amount_cents' => 9900,
                'due_date' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('company_license_invoices', 0);
    }

    private function createPlatformAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'is_platform_admin' => true,
            'email_verified_at' => now(),
        ]);

        return $admin;
    }

    /**
     * @return array{Group, Company, CompanyLicense}
     */
    private function createCustomerScenario(bool $trialEnded = true): array
    {
        $owner = User::factory()->create();

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Cliente Teste',
            'type' => GroupType::Customer->value,
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '55111222000110',
            'legal_name' => 'Cliente Alfa LTDA',
            'trade_name' => 'Cliente Alfa',
            'tax_regime' => 'simples',
        ]);

        $group->users()->attach($owner->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        $owner->companies()->attach($company->getKey());

        $license = CompanyLicense::query()->where('company_id', $company->getKey())->firstOrFail();
        $license->forceFill([
            'status' => CompanyLicenseStatus::Trialing,
            'trial_starts_at' => now()->subDays(8),
            'trial_ends_at' => $trialEnded ? now()->subDay() : now()->addDay(),
            'monthly_price_cents' => 9900,
        ])->save();

        return [$group, $company, $license];
    }
}
