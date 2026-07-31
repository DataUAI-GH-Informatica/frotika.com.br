<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Models\FinancialEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateFinancialEntryRequest extends FinancialEntryRequest
{
    public function authorize(): bool
    {
        $entry = FinancialEntry::query()->find($this->route('entry'));

        return $entry instanceof FinancialEntry && Gate::allows('update', $entry);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'apply_scope' => ['nullable', Rule::in(['single', 'forward', 'all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'apply_scope' => (string) ($this->input('apply_scope') ?: 'single'),
        ]);
    }
}
