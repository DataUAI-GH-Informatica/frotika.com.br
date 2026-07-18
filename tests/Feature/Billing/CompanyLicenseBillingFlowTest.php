<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CompanyLicenseBillingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_da_empresa_principal_emite_boleto_manual_para_licenca_do_grupo(): void
    {
        [$owner, $group, $primaryCompany, $secondaryCompany, $secondaryLicense] = $this->createScenarioWithPrimaryCompany();

        $response = $this
            ->actingAs($owner)
            ->post(route('billing.licenses.issue', ['license' => $secondaryLicense->getKey()]), [
                'amount_cents' => 12990,
                'due_date' => now()->addDays(3)->toDateString(),
                'reference_month' => now()->format('Y-m'),
                'boleto_number' => '34191.79001 01043.510047 91020.150008 9 99990000012990',
                'boleto_url' => 'https://pagamentos.exemplo.com/boleto/12990',
                'boleto_pdf_url' => 'https://pagamentos.exemplo.com/boleto/12990.pdf',
            ]);

        $response->assertRedirect(route('billing.licenses.index'));
        $response->assertSessionHas('status', 'Boleto lançado com sucesso para a licença selecionada.');

        $this->assertDatabaseHas('company_license_invoices', [
            'company_license_id' => $secondaryLicense->getKey(),
            'group_id' => $group->getKey(),
            'company_id' => $secondaryCompany->getKey(),
            'amount_cents' => 12990,
            'status' => CompanyLicenseInvoiceStatus::Pending->value,
            'created_by_user_id' => $owner->getKey(),
        ]);

        $this->assertDatabaseHas('company_licenses', [
            'id' => $secondaryLicense->getKey(),
            'status' => CompanyLicenseStatus::PendingPayment->value,
        ]);

        $this->assertDatabaseHas('groups', [
            'id' => $group->getKey(),
            'primary_company_id' => $primaryCompany->getKey(),
        ]);
    }

    public function test_emissao_de_boleto_e_bloqueada_antes_do_fim_do_trial(): void
    {
        [$owner, , , , $secondaryLicense] = $this->createScenarioWithPrimaryCompany(trialEnded: false);

        $response = $this
            ->actingAs($owner)
            ->from(route('billing.licenses.index'))
            ->post(route('billing.licenses.issue', ['license' => $secondaryLicense->getKey()]), [
                'amount_cents' => 9900,
                'due_date' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertRedirect(route('billing.licenses.index'));
        $response->assertSessionHasErrors(['due_date']);

        $this->assertDatabaseCount('company_license_invoices', 0);
        $this->assertDatabaseHas('company_licenses', [
            'id' => $secondaryLicense->getKey(),
            'status' => CompanyLicenseStatus::Trialing->value,
        ]);
    }

    public function test_baixa_manual_marca_boleto_como_pago_e_ativa_licenca(): void
    {
        [$owner, $group, , $secondaryCompany, $secondaryLicense] = $this->createScenarioWithPrimaryCompany();

        $invoice = CompanyLicenseInvoice::query()->create([
            'company_license_id' => $secondaryLicense->getKey(),
            'group_id' => $group->getKey(),
            'company_id' => $secondaryCompany->getKey(),
            'reference_month' => now()->startOfMonth()->toDateString(),
            'amount_cents' => 15990,
            'due_date' => now()->addDays(1)->toDateString(),
            'status' => CompanyLicenseInvoiceStatus::Pending,
            'boleto_url' => 'https://pagamentos.exemplo.com/boleto/15990',
        ]);

        $secondaryLicense->forceFill([
            'status' => CompanyLicenseStatus::PendingPayment,
        ])->save();

        $response = $this
            ->actingAs($owner)
            ->post(route('billing.licenses.mark-paid', ['invoice' => $invoice->getKey()]), [
                'paid_at' => now()->toDateString(),
                'paid_note' => 'Conferido no banco manualmente',
            ]);

        $response->assertRedirect(route('billing.licenses.index'));
        $response->assertSessionHas('status', 'Pagamento confirmado manualmente com sucesso.');

        $this->assertDatabaseHas('company_license_invoices', [
            'id' => $invoice->getKey(),
            'status' => CompanyLicenseInvoiceStatus::Paid->value,
            'paid_note' => 'Conferido no banco manualmente',
            'confirmed_by_user_id' => $owner->getKey(),
        ]);

        $this->assertDatabaseHas('company_licenses', [
            'id' => $secondaryLicense->getKey(),
            'status' => CompanyLicenseStatus::Active->value,
        ]);

        $this->assertNotNull($secondaryLicense->fresh()->activated_at);
    }

    public function test_usuario_fora_da_empresa_principal_nao_consegue_emitir_boleto(): void
    {
        [$owner, , , $secondaryCompany, $secondaryLicense] = $this->createScenarioWithPrimaryCompany();

        $owner->forceFill([
            'current_company_id' => $secondaryCompany->getKey(),
        ])->save();

        $response = $this
            ->actingAs($owner)
            ->post(route('billing.licenses.issue', ['license' => $secondaryLicense->getKey()]), [
                'amount_cents' => 9900,
                'due_date' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('company_license_invoices', 0);
    }

    public function test_tela_de_licencas_exibe_boleto_para_quitacao_do_cliente(): void
    {
        [$owner, $group, , $secondaryCompany, $secondaryLicense] = $this->createScenarioWithPrimaryCompany();

        /** @var User $employee */
        $employee = User::factory()->create();
        $group->users()->attach($employee->getKey(), [
            'role' => 'manager',
            'invited_by' => $owner->getKey(),
            'joined_at' => now(),
        ]);
        $secondaryCompany->users()->attach($employee->getKey());

        $employee->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $secondaryCompany->getKey(),
        ])->save();

        CompanyLicenseInvoice::query()->create([
            'company_license_id' => $secondaryLicense->getKey(),
            'group_id' => $group->getKey(),
            'company_id' => $secondaryCompany->getKey(),
            'reference_month' => now()->startOfMonth()->toDateString(),
            'amount_cents' => 10990,
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => CompanyLicenseInvoiceStatus::Pending,
            'boleto_url' => 'https://pagamentos.exemplo.com/boleto/abc',
            'boleto_pdf_url' => 'https://pagamentos.exemplo.com/boleto/abc.pdf',
        ]);

        $response = $this
            ->actingAs($employee)
            ->get(route('billing.licenses.index'));

        $response->assertOk();
        $response->assertSee('Quitar boleto');
        $response->assertSee('https://pagamentos.exemplo.com/boleto/abc', false);
        $response->assertSee('Somente a empresa principal pode lançar ou baixar boletos.');
    }

    /**
     * @return array{User, Group, Company, Company, CompanyLicense}
     */
    private function createScenarioWithPrimaryCompany(bool $trialEnded = true): array
    {
        $owner = User::factory()->create();

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Cobrança Teste',
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $primaryCompany = $this->createCompany($group, 'Empresa Principal', '55111222000110');
        $secondaryCompany = $this->createCompany($group, 'Filial Sul', '55111222000111');

        $group->users()->attach($owner->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        $owner->companies()->attach([$primaryCompany->getKey(), $secondaryCompany->getKey()]);

        $owner->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $primaryCompany->getKey(),
        ])->save();

        $primaryLicense = CompanyLicense::query()->where('company_id', $primaryCompany->getKey())->firstOrFail();
        $primaryLicense->forceFill([
            'status' => CompanyLicenseStatus::Active,
            'trial_starts_at' => now()->subDays(8),
            'trial_ends_at' => now()->subDay(),
            'activated_at' => now()->subDay(),
            'monthly_price_cents' => 9900,
        ])->save();

        $secondaryLicense = CompanyLicense::query()->where('company_id', $secondaryCompany->getKey())->firstOrFail();
        $secondaryLicense->forceFill([
            'status' => CompanyLicenseStatus::Trialing,
            'trial_starts_at' => now()->subDays(2),
            'trial_ends_at' => $trialEnded ? now()->subDay() : now()->addDay(),
            'monthly_price_cents' => 9900,
        ])->save();

        return [$owner, $group, $primaryCompany, $secondaryCompany, $secondaryLicense];
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
}
