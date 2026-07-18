<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Models;

use App\Domain\Billing\Models\GroupLicense;
use App\Domain\Tenancy\Enums\GroupType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property GroupType $type
 */
final class Group extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => GroupType::class,
        ];
    }

    public function isPlatform(): bool
    {
        return $this->type === GroupType::Platform;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function primaryCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'primary_company_id');
    }

    /**
     * @return HasOne<GroupLicense, $this>
     */
    public function license(): HasOne
    {
        return $this->hasOne(GroupLicense::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }
}
