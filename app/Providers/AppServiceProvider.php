<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('switch-company', static function (User $user, Company $company): bool {
            return $user->companies()->whereKey($company->getKey())->exists()
                && $user->groups()->whereKey($company->getAttribute('group_id'))->exists();
        });
    }
}
