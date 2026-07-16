<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Domain\Tenancy\Models\Company;
use Closure;

final class TenantContext
{
    private ?Company $company = null;

    public function company(): ?Company
    {
        return $this->company;
    }

    public function companyId(): ?int
    {
        return $this->company?->getKey();
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runFor(Company $company, Closure $callback): mixed
    {
        $previousCompany = $this->company;
        $this->company = $company;

        try {
            return $callback();
        } finally {
            $this->company = $previousCompany;
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runWithoutTenant(Closure $callback): mixed
    {
        $previousCompany = $this->company;
        $this->company = null;

        try {
            return $callback();
        } finally {
            $this->company = $previousCompany;
        }
    }
}
