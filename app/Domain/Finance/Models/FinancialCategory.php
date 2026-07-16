<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Enums\FinancialCategoryAllocation;
use App\Domain\Finance\Enums\FinancialCategoryDreGroup;
use App\Domain\Finance\Enums\FinancialCategoryType;
use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => FinancialCategoryType::class,
            'dre_group' => FinancialCategoryDreGroup::class,
            'allocation' => FinancialCategoryAllocation::class,
            'affects_cashflow' => 'boolean',
            'is_system' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
