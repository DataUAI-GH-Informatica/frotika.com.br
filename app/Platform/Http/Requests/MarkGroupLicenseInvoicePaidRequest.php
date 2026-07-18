<?php

declare(strict_types=1);

namespace App\Platform\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class MarkGroupLicenseInvoicePaidRequest extends FormRequest
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
