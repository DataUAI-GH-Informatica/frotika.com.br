<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Domain\Attachments\Actions\StoreAttachment;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Orquestração do campo `attachments[]` presente em formulários de outros
 * módulos. A regra de anexo continua toda na Action.
 */
trait HandlesAttachmentUploads
{
    /**
     * @return int quantidade de arquivos guardados
     */
    protected function attachUploadedFiles(
        Request $request,
        StoreAttachment $action,
        User $actor,
        Company $company,
        Model $attachable,
    ): int {
        $files = array_filter(
            (array) $request->file('attachments', []),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        );

        foreach ($files as $file) {
            $action->execute($actor, $company, $attachable, $file);
        }

        return count($files);
    }
}
