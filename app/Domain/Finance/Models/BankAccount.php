<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'initial_balance_at' => 'date',
            'is_default' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
