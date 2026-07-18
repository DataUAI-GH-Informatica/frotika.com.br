<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Domain\Tenancy\Observers\CompanyObserver;
use App\Models\User;
use App\Support\Format;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Company::observe(CompanyObserver::class);

        // Alias global para usar Format:: direto na Blade (seção 14.3 do blueprint).
        AliasLoader::getInstance()->alias('Format', Format::class);

        Gate::define('switch-company', static function (User $user, Company $company): bool {
            return $user->companies()->whereKey($company->getKey())->exists()
                && $user->groups()->whereKey($company->getAttribute('group_id'))->exists();
        });

        Gate::define('manage-company-licenses', static function (User $user, Group $group): bool {
            if ((int) $group->owner_user_id !== $user->getKey()) {
                return false;
            }

            if ((int) $user->current_group_id !== $group->getKey()) {
                return false;
            }

            if ($user->current_company_id === null) {
                return false;
            }

            return CompanyLicense::query()
                ->where('group_id', $group->getKey())
                ->where('company_id', $user->current_company_id)
                ->where('is_primary', true)
                ->exists();
        });

        View::composer('layouts.app', static function ($view): void {
            $user = Auth::user();

            if (! $user instanceof User || $user->current_group_id === null) {
                $view->with('topbarCompanies', collect());
                $view->with('topbarCurrentCompanyId', null);
                $view->with('topbarCurrentCompanyName', null);
                $view->with('licenseBanner', null);

                return;
            }

            $companies = $user->companies()
                ->where('group_id', $user->current_group_id)
                ->orderBy('trade_name')
                ->get(['companies.id', 'companies.trade_name']);

            $companyIds = $companies->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

            $licensesByCompanyId = CompanyLicense::query()
                ->where('group_id', $user->current_group_id)
                ->whereIn('company_id', $companyIds)
                ->get(['company_id', 'status'])
                ->keyBy('company_id');

            $currentCompanyStatus = null;

            if ($user->current_company_id !== null) {
                $currentCompanyStatus = $licensesByCompanyId
                    ->get($user->current_company_id)
                    ?->status;
            }

            $topbarCompanyStatusMarkers = [];

            foreach ($companies as $companyOption) {
                $licenseStatus = $licensesByCompanyId
                    ->get($companyOption->getKey())
                    ?->status;

                if (! $licenseStatus instanceof CompanyLicenseStatus) {
                    continue;
                }

                if ($currentCompanyStatus instanceof CompanyLicenseStatus && $licenseStatus === $currentCompanyStatus) {
                    continue;
                }

                $topbarCompanyStatusMarkers[$companyOption->getKey()] = match ($licenseStatus) {
                    CompanyLicenseStatus::Active => 'Ativa',
                    CompanyLicenseStatus::Trialing => 'Trial',
                    CompanyLicenseStatus::PendingPayment => 'Bloqueada',
                    CompanyLicenseStatus::Suspended => 'Suspensa',
                };
            }

            $currentCompanyName = $companies
                ->firstWhere('id', $user->current_company_id)
                ?->getAttribute('trade_name');

            $licenseBanner = null;

            if ($user->current_company_id !== null) {
                /** @var CompanyLicense|null $currentLicense */
                $currentLicense = CompanyLicense::query()
                    ->where('group_id', $user->current_group_id)
                    ->where('company_id', $user->current_company_id)
                    ->first();

                if ($currentLicense !== null && ! in_array($currentLicense->status, [CompanyLicenseStatus::Active, CompanyLicenseStatus::Trialing], true)) {
                    $group = Group::query()
                        ->with('owner:id,name')
                        ->find($user->current_group_id);

                    $canManageLicenses = $group !== null
                        ? Gate::forUser($user)->allows('manage-company-licenses', $group)
                        : false;

                    /** @var CompanyLicenseInvoice|null $openInvoice */
                    $openInvoice = $currentLicense->invoices()
                        ->whereIn('status', [
                            CompanyLicenseInvoiceStatus::Pending->value,
                            CompanyLicenseInvoiceStatus::Overdue->value,
                        ])
                        ->orderBy('due_date')
                        ->first();

                    $licenseBanner = [
                        'status_label' => $currentLicense->status->label(),
                        'status_value' => $currentLicense->status->value,
                        'due_date' => $openInvoice?->due_date,
                        'amount_cents' => $openInvoice?->amount_cents,
                        'boleto_url' => $openInvoice?->boleto_url,
                        'boleto_pdf_url' => $openInvoice?->boleto_pdf_url,
                        'owner_name' => $group?->owner?->name,
                        'can_manage' => $canManageLicenses,
                    ];
                }
            }

            $view->with('topbarCompanies', $companies);
            $view->with('topbarCurrentCompanyId', $user->current_company_id);
            $view->with('topbarCurrentCompanyName', $currentCompanyName);
            $view->with('topbarCompanyStatusMarkers', $topbarCompanyStatusMarkers);
            $view->with('licenseBanner', $licenseBanner);
        });
    }
}
