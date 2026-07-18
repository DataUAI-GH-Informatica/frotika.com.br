<?php

declare(strict_types=1);

namespace App\Http\Requests\Fuelings;

use App\Domain\Fuelings\Models\Fueling;
use Illuminate\Support\Facades\Gate;

final class UpdateFuelingRequest extends FuelingRequest
{
    public function authorize(): bool
    {
        $fueling = $this->route('fueling');

        if (! $fueling instanceof Fueling) {
            $fueling = Fueling::query()->find($fueling);
        }

        return $fueling instanceof Fueling && Gate::allows('update', $fueling);
    }
}
