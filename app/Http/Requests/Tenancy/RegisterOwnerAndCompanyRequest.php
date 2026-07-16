<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class RegisterOwnerAndCompanyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'group_name' => ['required', 'string', 'max:120'],
            'company_legal_name' => ['required', 'string', 'max:150'],
            'company_trade_name' => ['required', 'string', 'max:150'],
            'company_cnpj' => [
                'required',
                'string',
                'size:14',
                'unique:companies,cnpj',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! $this->isValidCnpj($value)) {
                        $fail('O CNPJ da empresa informado e invalido.');
                    }
                },
            ],
            'tax_regime' => ['nullable', 'string', 'in:simples,presumido,real'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute e obrigatorio.',
            'email.email' => 'Informe um e-mail valido.',
            'email.unique' => 'Este e-mail ja esta cadastrado.',
            'password.min' => 'A senha deve ter ao menos :min caracteres.',
            'company_cnpj.size' => 'O CNPJ da empresa deve ter 14 digitos.',
            'company_cnpj.unique' => 'Este CNPJ ja esta cadastrado.',
            'tax_regime.in' => 'Regime tributario invalido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
            'group_name' => 'nome do grupo',
            'company_legal_name' => 'razao social',
            'company_trade_name' => 'nome fantasia',
            'company_cnpj' => 'CNPJ da empresa',
            'tax_regime' => 'regime tributario',
        ];
    }

    protected function prepareForValidation(): void
    {
        $companyCnpj = (string) $this->input('company_cnpj', '');
        $normalizedCnpj = preg_replace('/\D+/', '', $companyCnpj) ?? $companyCnpj;

        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email', ''))),
            'company_cnpj' => $normalizedCnpj,
            'tax_regime' => (string) ($this->input('tax_regime', 'simples') ?: 'simples'),
        ]);
    }

    private function isValidCnpj(string $cnpj): bool
    {
        if (preg_match('/^\d{14}$/', $cnpj) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $firstDigit = $this->calculateCnpjDigit(substr($cnpj, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $this->calculateCnpjDigit(substr($cnpj, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $cnpj[12] === (string) $firstDigit && $cnpj[13] === (string) $secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function calculateCnpjDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $base[$index]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Os dados informados sao invalidos.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
