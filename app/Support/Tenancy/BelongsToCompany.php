<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\Exceptions\MissingTenantContextException;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;
use LogicException;

/** @mixin Model */
trait BelongsToCompany
{
    /**
     * @param  Scope|Closure|string  $scope
     */
    abstract public static function addGlobalScope($scope, $implementation = null);

    /**
     * @param  callable(Model): void  $callback
     */
    abstract public static function creating($callback);

    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model): void {
            $companyId = app(TenantContext::class)->companyId();

            if ($companyId === null) {
                throw MissingTenantContextException::forModel($model::class);
            }

            $currentCompanyId = $model->getAttribute('company_id');

            if ($currentCompanyId === null) {
                $model->setAttribute('company_id', $companyId);

                return;
            }

            if ((int) $currentCompanyId !== $companyId) {
                throw new LogicException('Model company_id does not match active tenant context.');
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
