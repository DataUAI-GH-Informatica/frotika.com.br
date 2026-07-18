<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Policies;

use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;

final class FuelingPolicy
{
    /**
     * @var list<string>
     */
    private const MANAGER_ROLES = ['owner', 'admin'];

    public function viewAny(User $user): bool
    {
        return $user->current_group_id !== null;
    }

    public function view(User $user, Fueling $fueling): bool
    {
        return $this->sharesGroup($user, $fueling);
    }

    public function create(User $user): bool
    {
        return $user->current_group_id !== null
            && $this->manages($user, (int) $user->current_group_id);
    }

    public function update(User $user, Fueling $fueling): bool
    {
        $groupId = $this->groupIdOf($fueling);

        return $groupId !== null && $this->manages($user, $groupId);
    }

    public function delete(User $user, Fueling $fueling): bool
    {
        return $this->update($user, $fueling);
    }

    private function sharesGroup(User $user, Fueling $fueling): bool
    {
        $groupId = $this->groupIdOf($fueling);

        return $groupId !== null && $user->groups()->whereKey($groupId)->exists();
    }

    private function groupIdOf(Fueling $fueling): ?int
    {
        $company = Company::query()->find($fueling->getAttribute('company_id'));

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
