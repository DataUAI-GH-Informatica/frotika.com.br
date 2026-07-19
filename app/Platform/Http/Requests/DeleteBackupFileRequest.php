<?php

declare(strict_types=1);

namespace App\Platform\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteBackupFileRequest extends FormRequest
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
            'file' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Informe o arquivo de backup para exclusao.',
            'file.max' => 'Nome de arquivo invalido para exclusao.',
        ];
    }
}
