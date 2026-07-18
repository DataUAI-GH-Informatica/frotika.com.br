<?php

declare(strict_types=1);

namespace App\Http\Requests\Fuelings;

use App\Domain\Fuelings\Models\Fueling;
use Illuminate\Support\Facades\Gate;

final class StoreFuelingRequest extends FuelingRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Fueling::class);
    }
}
