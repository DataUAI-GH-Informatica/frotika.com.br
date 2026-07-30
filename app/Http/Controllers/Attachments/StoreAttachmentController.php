<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attachments;

use App\Domain\Attachments\Actions\StoreAttachment;
use App\Domain\Attachments\Enums\AttachableType;
use App\Domain\Tenancy\Models\Company;
use App\Http\Controllers\Concerns\HandlesAttachmentUploads;
use App\Http\Requests\Attachments\StoreAttachmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

final class StoreAttachmentController
{
    use HandlesAttachmentUploads;

    public function __invoke(
        StoreAttachmentRequest $request,
        string $owner,
        int $id,
        StoreAttachment $action,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa antes de anexar arquivos.');
        }

        $type = AttachableType::fromSlug($owner);

        if ($type === null) {
            abort(404);
        }

        $modelClass = $type->modelClass();

        /** @var Model $attachable */
        $attachable = $modelClass::query()->findOrFail($id);

        $count = $this->attachUploadedFiles($request, $action, $user, $company, $attachable);

        return back()->with('status', $count === 1
            ? 'Anexo adicionado.'
            : $count.' anexos adicionados.');
    }
}
