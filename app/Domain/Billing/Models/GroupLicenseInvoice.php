<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\GroupLicenseInvoiceStatus;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property GroupLicenseInvoiceStatus $status
 */
class GroupLicenseInvoice extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => GroupLicenseInvoiceStatus::class,
            'reference_month' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GroupLicense, $this>
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(GroupLicense::class, 'group_license_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
