<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenances;

use App\Domain\Maintenances\Models\Maintenance;
use Illuminate\Support\Facades\Gate;

final class UpdateMaintenanceRequest extends MaintenanceRequest
{
    public function authorize(): bool
    {
        $maintenance = $this->route('maintenance');

        if (! $maintenance instanceof Maintenance) {
            $maintenance = Maintenance::query()->find($maintenance);
        }

        return $maintenance instanceof Maintenance && Gate::allows('update', $maintenance);
    }
}
