<?php

declare(strict_types=1);

namespace App\Domain\Maintenances\Actions;

use App\Domain\Maintenances\Models\Maintenance;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;

final class DeleteMaintenance
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function execute(User $actor, Company $company, Maintenance $maintenance): void
    {
        Gate::forUser($actor)->authorize('delete', $maintenance);

        $this->tenant->runFor($company, function () use ($maintenance): void {
            // Soft delete dispara o observer, que cancela o lançamento financeiro.
            $maintenance->delete();
        });
    }
}
