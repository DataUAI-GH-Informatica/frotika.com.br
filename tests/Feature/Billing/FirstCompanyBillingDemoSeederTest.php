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
use Database\Seeders\FirstCompanyBillingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FirstCompanyBillingDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_cria_empresa_gh_com_licenca_pendente_e_boleto_em_aberto(): void
    {
        $this->seed(FirstCompanyBillingDemoSeeder::class);

        $user = User::query()->where('email', 'gestor@frotika.com.br')->first();
        $this->assertNotNull($user);

        $company = Company::query()->where('cnpj', '29426759000112')->first();
        $this->assertNotNull($company);

        $group = Group::query()->find($company?->group_id);
        $this->assertNotNull($group);

        $this->assertSame('GH INFORMATICA LTDA', $company?->legal_name);
        $this->assertSame('GH INFORMATICA', $company?->trade_name);
        $this->assertSame('LAVRAS', $company?->city);
        $this->assertSame('MG', $company?->state);

        $license = CompanyLicense::query()->where('company_id', $company?->getKey())->first();
        $this->assertNotNull($license);
        $this->assertSame(CompanyLicenseStatus::PendingPayment, $license?->status);
        $this->assertTrue((bool) $license?->is_primary);

        $invoice = CompanyLicenseInvoice::query()
            ->where('company_license_id', $license?->getKey())
            ->where('status', CompanyLicenseInvoiceStatus::Pending->value)
            ->first();

        $this->assertNotNull($invoice);
        $this->assertSame('https://frotika.com.br/boletos/gh-informatica', $invoice?->boleto_url);

        $this->assertDatabaseHas('groups', [
            'id' => $group?->getKey(),
            'primary_company_id' => $company?->getKey(),
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user?->getKey(),
            'current_group_id' => $group?->getKey(),
            'current_company_id' => $company?->getKey(),
        ]);
    }
}
