<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Enums\FinancialEntryCancelScope;
use App\Domain\Finance\Models\FinancialEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class CancelFinancialEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = FinancialEntry::query()->find($this->route('entry'));

        return $entry instanceof FinancialEntry && Gate::allows('delete', $entry);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['nullable', Rule::in([
                FinancialEntryCancelScope::Single->value,
                FinancialEntryCancelScope::Forward->value,
                FinancialEntryCancelScope::All->value,
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scope' => (string) ($this->input('scope') ?: FinancialEntryCancelScope::Single->value),
        ]);
    }
}
