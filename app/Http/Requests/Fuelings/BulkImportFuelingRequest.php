<?php

declare(strict_types=1);

namespace App\Http\Requests\Fuelings;

use App\Domain\Fuelings\Models\Fueling;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class BulkImportFuelingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Fueling::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'sheet' => ['required', 'file', 'max:4096', 'extensions:xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sheet.required' => 'Escolha a planilha de abastecimentos.',
            'sheet.extensions' => 'Envie a planilha no formato .xlsx.',
            'sheet.max' => 'A planilha passa de 4 MB. Divida em arquivos menores.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sheet' => 'planilha',
        ];
    }
}
