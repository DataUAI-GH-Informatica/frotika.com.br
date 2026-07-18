<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenances;

use App\Domain\Maintenances\Models\Maintenance;
use Illuminate\Support\Facades\Gate;

final class StoreMaintenanceRequest extends MaintenanceRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Maintenance::class);
    }
}
