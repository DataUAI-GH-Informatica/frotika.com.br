<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Models;

use App\Domain\Billing\Models\CompanyLicense;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Company extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    /**
     * @return HasOne<CompanyLicense, $this>
     */
    public function license(): HasOne
    {
        return $this->hasOne(CompanyLicense::class);
    }
}
