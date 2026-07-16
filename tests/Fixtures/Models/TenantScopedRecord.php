<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

final class TenantScopedRecord extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $table = 'tenant_scoped_records';

    protected $guarded = [];
}
