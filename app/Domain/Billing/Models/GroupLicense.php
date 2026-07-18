<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Tenancy\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property GroupLicenseStatus $status
 */
class GroupLicense extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => GroupLicenseStatus::class,
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
     * @return HasMany<GroupLicenseInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(GroupLicenseInvoice::class);
    }

    /**
     * @return HasOne<GroupLicenseInvoice, $this>
     */
    public function latestInvoice(): HasOne
    {
        return $this->hasOne(GroupLicenseInvoice::class)->latestOfMany('due_date');
    }
}
