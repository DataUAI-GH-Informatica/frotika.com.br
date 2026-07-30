<?php

declare(strict_types=1);

namespace App\Http\Requests\Attachments;

use App\Domain\Attachments\Support\AttachmentRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAttachmentRequest extends FormRequest
{
    /**
     * A permissão depende do registro dono, resolvido no controller e conferido
     * pela Action com Gate::authorize('attach', ...).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachments' => array_merge(['required'], AttachmentRules::collectionRules()),
            'attachments.*' => AttachmentRules::fileRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.required' => 'Escolha ao menos um arquivo.',
            'attachments.max' => 'Envie no máximo '.AttachmentRules::maxFiles().' arquivos por vez.',
            'attachments.*.mimes' => 'O Frotika aceita '.AttachmentRules::humanExtensions().'.',
            'attachments.*.max' => 'Cada arquivo pode ter até '.AttachmentRules::humanMaxSize().'.',
            'attachments.*.file' => 'O envio do arquivo falhou no meio do caminho. Tente de novo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'attachments' => 'anexos',
        ];
    }
}
