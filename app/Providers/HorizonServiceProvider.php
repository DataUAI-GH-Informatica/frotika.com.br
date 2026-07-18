<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Quem pode abrir o painel do Horizon fora do ambiente local.
     * Apenas o administrador da plataforma (dono do sistema Frotika).
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static function (?User $user = null): bool {
            return $user instanceof User && $user->isPlatformAdmin();
        });
    }
}
