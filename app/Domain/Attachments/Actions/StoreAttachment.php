<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Actions;

use App\Domain\Attachments\Enums\AttachableType;
use App\Domain\Attachments\Models\Attachment;
use App\Domain\Attachments\Support\AttachmentRules;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class StoreAttachment
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    public function execute(User $actor, Company $company, Model $attachable, UploadedFile $file): Attachment
    {
        Gate::forUser($actor)->authorize('attach', $attachable);

        return $this->tenant->runFor($company, function () use ($actor, $company, $attachable, $file): Attachment {
            $type = AttachableType::forModel($attachable);

            $this->guardOwnership($company, $attachable);
            $this->guardFile($file);

            $disk = (string) config('attachments.storage_disk', 'local');
            $extension = $this->extension($file);
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $size = (int) $file->getSize();

            $path = $file->storeAs(
                $this->directory($company, $type, (int) $attachable->getKey()),
                Str::uuid()->toString().'.'.$extension,
                $disk,
            );

            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages([
                    'attachments' => 'Não foi possível guardar o arquivo. Tente enviar de novo.',
                ]);
            }

            return Attachment::query()->create([
                'attachable_type' => $attachable::class,
                'attachable_id' => $attachable->getKey(),
                'disk' => $disk,
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'mime' => $mime,
                'size_bytes' => $size,
                'uploaded_by' => $actor->getKey(),
            ]);
        });
    }

    /**
     * O global scope já filtra por empresa, mas um anexo apontando para o
     * registro errado é dado de um cliente na tela de outro — vale a checagem
     * explícita.
     */
    private function guardOwnership(Company $company, Model $attachable): void
    {
        if ((int) $attachable->getAttribute('company_id') !== (int) $company->getKey()) {
            throw new LogicException('Não é possível anexar em registro de outra empresa.');
        }
    }

    private function guardFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'attachments' => 'O envio do arquivo falhou no meio do caminho. Tente de novo.',
            ]);
        }

        if (! in_array($this->extension($file), AttachmentRules::allowedExtensions(), true)) {
            throw ValidationException::withMessages([
                'attachments' => 'O Frotika aceita '.AttachmentRules::humanExtensions().'.',
            ]);
        }

        if ((int) $file->getSize() > AttachmentRules::maxKilobytes() * 1024) {
            throw ValidationException::withMessages([
                'attachments' => 'Cada arquivo pode ter até '.AttachmentRules::humanMaxSize().'.',
            ]);
        }
    }

    private function extension(UploadedFile $file): string
    {
        return mb_strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
    }

    private function directory(Company $company, AttachableType $type, int $ownerId): string
    {
        $group = Group::query()->find($company->getAttribute('group_id'));

        return sprintf(
            'grupos/%s/anexos/%s/%d',
            $group?->getAttribute('uuid') ?? 'sem-grupo',
            $type->slug(),
            $ownerId,
        );
    }
}
