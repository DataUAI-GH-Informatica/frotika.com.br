<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

final class MarkCompanyLicenseInvoicePaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'paid_at' => ['nullable', 'date'],
            'paid_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paid_at.date' => 'Data de pagamento inválida.',
            'paid_note.max' => 'A observação de pagamento deve ter no máximo 255 caracteres.',
        ];
    }
}
