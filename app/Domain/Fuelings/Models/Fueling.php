<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Models;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Enums\FuelingPaymentMethod;
use App\Domain\Fuelings\Enums\FuelProduct;
use App\Domain\Fuelings\Enums\FuelTank;
use App\Models\User;
use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property FuelProduct $product
 * @property FuelTank $tank
 * @property FuelingPaymentMethod $payment_method
 * @property \Illuminate\Support\Carbon $fueled_at
 * @property int|null $km_since_last
 * @property numeric-string|null $km_per_liter
 */
final class Fueling extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'product' => FuelProduct::class,
            'tank' => FuelTank::class,
            'payment_method' => FuelingPaymentMethod::class,
            'fueled_at' => 'datetime',
            'odometer' => 'integer',
            'liters' => 'decimal:3',
            'price_per_liter' => 'decimal:3',
            'total_cents' => 'integer',
            'full_tank' => 'boolean',
            'km_since_last' => 'integer',
            'km_per_liter' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
