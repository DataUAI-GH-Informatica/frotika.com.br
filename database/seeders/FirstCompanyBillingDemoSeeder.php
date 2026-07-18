<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class FirstCompanyBillingDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::query()->firstOrNew([
                'email' => 'gestor@frotika.com.br',
            ]);

            $user->forceFill([
                'name' => 'Gestor Frotika',
                'email' => 'gestor@frotika.com.br',
                'email_verified_at' => now(),
                'password' => Hash::make('secret-1234'),
            ])->save();

            $group = Group::query()->firstOrNew([
                'name' => 'Grupo GH Informática',
                'owner_user_id' => $user->getKey(),
            ]);

            $group->forceFill([
                'uuid' => $group->uuid ?: Str::uuid()->toString(),
                'type' => 'customer',
                'status' => 'active',
            ])->save();

            // Dados da empresa vindos da BrasilAPI para o CNPJ 29.426.759/0001-12.
            $company = Company::query()->firstOrNew([
                'cnpj' => '29426759000112',
            ]);

            $company->forceFill([
                'group_id' => $group->getKey(),
                'uuid' => $company->uuid ?: Str::uuid()->toString(),
                'legal_name' => 'GH INFORMATICA LTDA',
                'trade_name' => 'GH INFORMATICA',
                'tax_regime' => 'simples',
                'zip_code' => '37200-154',
                'street' => 'PRACA DOUTOR AUGUSTO SILVA',
                'number' => '580',
                'complement' => null,
                'district' => 'CENTRO',
                'city' => 'LAVRAS',
                'state' => 'MG',
                'ibge_code' => '3138203',
                'phone' => '3592360236',
                'email' => null,
            ])->save();

            $group->users()->syncWithoutDetaching([
                $user->getKey() => [
                    'role' => 'owner',
                    'invited_by' => null,
                    'joined_at' => now(),
                ],
            ]);

            $company->users()->syncWithoutDetaching([$user->getKey()]);

            $group->forceFill([
                'primary_company_id' => $company->getKey(),
            ])->save();

            $user->forceFill([
                'current_group_id' => $group->getKey(),
                'current_company_id' => $company->getKey(),
            ])->save();

            $priceCents = (int) config('billing.company_license_monthly_price_cents', 9900);

            Subscription::query()->updateOrCreate(
                ['group_id' => $group->getKey()],
                [
                    'status' => 'past_due',
                    'started_at' => now()->subDays(30),
                    'trial_ends_at' => now()->subDays(23),
                    'current_period_start' => now()->startOfMonth(),
                    'current_period_end' => now()->endOfMonth(),
                    'price_cents' => $priceCents,
                ],
            );

            $license = CompanyLicense::query()->firstOrNew([
                'company_id' => $company->getKey(),
            ]);

            $license->forceFill([
                'group_id' => $group->getKey(),
                'is_primary' => true,
                'status' => CompanyLicenseStatus::PendingPayment,
                'trial_starts_at' => now()->subDays(10),
                'trial_ends_at' => now()->subDays(3),
                'activated_at' => null,
                'suspended_at' => now()->subDays(3),
                'monthly_price_cents' => $priceCents,
                'notes' => 'Seed de demonstração com bloqueio por licença pendente.',
            ])->save();

            CompanyLicenseInvoice::query()
                ->where('company_license_id', $license->getKey())
                ->whereIn('status', [
                    CompanyLicenseInvoiceStatus::Pending->value,
                    CompanyLicenseInvoiceStatus::Overdue->value,
                ])
                ->update([
                    'status' => CompanyLicenseInvoiceStatus::Canceled->value,
                ]);

            CompanyLicenseInvoice::query()->updateOrCreate(
                [
                    'company_license_id' => $license->getKey(),
                    'reference_month' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'group_id' => $group->getKey(),
                    'company_id' => $company->getKey(),
                    'amount_cents' => $priceCents,
                    'due_date' => now()->addDays(2)->toDateString(),
                    'status' => CompanyLicenseInvoiceStatus::Pending,
                    'boleto_number' => '34191.79001 01043.510047 91020.150008 9 99990000009900',
                    'boleto_url' => 'https://frotika.com.br/boletos/gh-informatica',
                    'boleto_pdf_url' => 'https://frotika.com.br/boletos/gh-informatica.pdf',
                    'paid_at' => null,
                    'paid_note' => null,
                    'created_by_user_id' => $user->getKey(),
                    'confirmed_by_user_id' => null,
                ],
            );
        });
    }
}
