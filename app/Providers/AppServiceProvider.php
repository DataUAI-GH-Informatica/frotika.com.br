<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Tenancy\Models\Company;
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
        // Alias global para usar Format:: direto na Blade (seção 14.3 do blueprint).
        AliasLoader::getInstance()->alias('Format', Format::class);

        Gate::define('switch-company', static function (User $user, Company $company): bool {
            return $user->companies()->whereKey($company->getKey())->exists()
                && $user->groups()->whereKey($company->getAttribute('group_id'))->exists();
        });

        View::composer('layouts.app', static function ($view): void {
            $user = Auth::user();

            if (! $user instanceof User || $user->current_group_id === null) {
                $view->with('topbarCompanies', collect());
                $view->with('topbarCurrentCompanyId', null);
                $view->with('topbarCurrentCompanyName', null);

                return;
            }

            $companies = $user->companies()
                ->where('group_id', $user->current_group_id)
                ->orderBy('trade_name')
                ->get(['companies.id', 'companies.trade_name']);

            $currentCompanyName = $companies
                ->firstWhere('id', $user->current_company_id)
                ?->getAttribute('trade_name');

            $view->with('topbarCompanies', $companies);
            $view->with('topbarCurrentCompanyId', $user->current_company_id);
            $view->with('topbarCurrentCompanyName', $currentCompanyName);
        });
    }
}
