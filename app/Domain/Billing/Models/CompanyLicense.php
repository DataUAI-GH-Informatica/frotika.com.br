<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CompanyLicenseStatus $status
 */
class CompanyLicense extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'status' => CompanyLicenseStatus::class,
            'trial_starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<CompanyLicenseInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(CompanyLicenseInvoice::class);
    }

    /**
     * @return HasOne<CompanyLicenseInvoice, $this>
     */
    public function latestInvoice(): HasOne
    {
        return $this->hasOne(CompanyLicenseInvoice::class)->latestOfMany('due_date');
    }
}
