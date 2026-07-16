<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use Illuminate\Foundation\Http\FormRequest;

final class SwitchCurrentCompanyRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Informe a empresa que deseja acessar.',
            'company_id.integer' => 'Empresa invalida.',
            'company_id.exists' => 'A empresa selecionada nao existe.',
        ];
    }
}
