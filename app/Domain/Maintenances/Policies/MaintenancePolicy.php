<?php

declare(strict_types=1);

namespace App\Domain\Maintenances\Policies;

use App\Domain\Maintenances\Models\Maintenance;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;

final class MaintenancePolicy
{
    /**
     * @var list<string>
     */
    private const MANAGER_ROLES = ['owner', 'admin'];

    public function viewAny(User $user): bool
    {
        return $user->current_group_id !== null;
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        return $this->sharesGroup($user, $maintenance);
    }

    public function create(User $user): bool
    {
        return $user->current_group_id !== null
            && $this->manages($user, (int) $user->current_group_id);
    }

    public function update(User $user, Maintenance $maintenance): bool
    {
        $groupId = $this->groupIdOf($maintenance);

        return $groupId !== null && $this->manages($user, $groupId);
    }

    public function delete(User $user, Maintenance $maintenance): bool
    {
        return $this->update($user, $maintenance);
    }

    private function sharesGroup(User $user, Maintenance $maintenance): bool
    {
        $groupId = $this->groupIdOf($maintenance);

        return $groupId !== null && $user->groups()->whereKey($groupId)->exists();
    }

    private function groupIdOf(Maintenance $maintenance): ?int
    {
        $company = Company::query()->find($maintenance->getAttribute('company_id'));

        return $company === null ? null : (int) $company->getAttribute('group_id');
    }

    private function manages(User $user, int $groupId): bool
    {
        return $user->groups()
            ->whereKey($groupId)
            ->wherePivotIn('role', self::MANAGER_ROLES)
            ->exists();
    }
}
