<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Policies;

use App\Domain\Tenancy\Models\Company;
use App\Models\User;

final class CompanyPolicy
{
    /**
     * Papéis do grupo autorizados a gerenciar empresas.
     *
     * @var list<string>
     */
    private const MANAGER_ROLES = ['owner', 'admin'];

    public function viewAny(User $user): bool
    {
        return $user->current_group_id !== null;
    }

    public function view(User $user, Company $company): bool
    {
        return $this->belongsToGroup($user, (int) $company->getAttribute('group_id'));
    }

    public function create(User $user): bool
    {
        return $user->current_group_id !== null
            && $this->manages($user, (int) $user->current_group_id);
    }

    public function update(User $user, Company $company): bool
    {
        return $this->manages($user, (int) $company->getAttribute('group_id'));
    }

    public function delete(User $user, Company $company): bool
    {
        return $this->manages($user, (int) $company->getAttribute('group_id'));
    }

    private function belongsToGroup(User $user, int $groupId): bool
    {
        return $user->groups()->whereKey($groupId)->exists();
    }

    private function manages(User $user, int $groupId): bool
    {
        return $user->groups()
            ->whereKey($groupId)
            ->wherePivotIn('role', self::MANAGER_ROLES)
            ->exists();
    }
}
