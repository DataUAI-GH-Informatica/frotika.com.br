<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Models\FinancialEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreFinancialEntryRequest extends FinancialEntryRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', FinancialEntry::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'launch_mode' => ['required', Rule::in(['single', 'monthly', 'installment'])],
            'reference_date' => ['required_if:launch_mode,monthly,installment', 'nullable', 'date'],
            'installments' => ['required_if:launch_mode,installment', 'nullable', 'integer', 'min:2'],
        ];
    }
}
