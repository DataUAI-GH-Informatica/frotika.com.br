<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Data;

use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;

final readonly class RegisterOwnerAndCompanyResult
{
    public function __construct(
        public User $user,
        public Group $group,
        public Company $company,
    ) {}
}
