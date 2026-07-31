<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Enums\FinancialEntryPaymentMethod;
use App\Domain\Finance\Models\FinancialEntry;
use App\Support\Money\Brl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class SettleFinancialEntryRequest extends FormRequest
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
        $methods = array_map(
            static fn (FinancialEntryPaymentMethod $method): string => $method->value,
            FinancialEntryPaymentMethod::cases(),
        );

        return [
            'bank_account_id' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in($methods)],
            'paid_amount_cents' => ['nullable', 'integer', 'min:1'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'interest_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bank_account_id' => 'conta bancária',
            'paid_at' => 'data de pagamento',
            'payment_method' => 'meio de pagamento',
            'paid_amount_cents' => 'valor pago',
            'discount_cents' => 'desconto',
            'interest_cents' => 'juros',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => ($value = trim((string) $this->input('payment_method', ''))) === '' ? null : $value,
            'paid_amount_cents' => Brl::toCents($this->input('paid_amount')),
            'discount_cents' => Brl::toCents($this->input('discount_amount')),
            'interest_cents' => Brl::toCents($this->input('interest_amount')),
        ]);
    }
}
