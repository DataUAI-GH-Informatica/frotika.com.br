<?php

declare(strict_types=1);

namespace App\Platform\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class IssueCompanyLicenseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isPlatformAdmin();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'reference_month' => ['nullable', 'date_format:Y-m'],
            'boleto_number' => ['nullable', 'string', 'max:100'],
            'boleto_url' => ['nullable', 'url', 'max:2048'],
            'boleto_pdf_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_cents.required' => 'Informe o valor da mensalidade em centavos.',
            'amount_cents.integer' => 'O valor da mensalidade deve ser numérico.',
            'amount_cents.min' => 'O valor da mensalidade deve ser maior que zero.',
            'due_date.required' => 'Informe a data de vencimento do boleto.',
            'due_date.date' => 'Data de vencimento inválida.',
            'reference_month.date_format' => 'A competência deve estar no formato AAAA-MM.',
            'boleto_url.url' => 'A URL do boleto está inválida.',
            'boleto_pdf_url.url' => 'A URL do PDF do boleto está inválida.',
        ];
    }
}
