<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use App\Domain\Tenancy\Models\Company;
use App\Support\Cnpj\Cnpj;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateCompanyRequest extends StoreCompanyRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company && Gate::allows('update', $company);
    }

    /**
     * @return array<string, list<Closure|string|Unique>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $company = $this->route('company');
        $companyId = $company instanceof Company ? $company->getKey() : null;

        $rules['cnpj'] = [
            'required',
            'string',
            'size:14',
            Rule::unique('companies', 'cnpj')->ignore($companyId),
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! Cnpj::isValid($value)) {
                    $fail('O CNPJ informado é inválido.');
                }
            },
        ];

        return $rules;
    }
}
