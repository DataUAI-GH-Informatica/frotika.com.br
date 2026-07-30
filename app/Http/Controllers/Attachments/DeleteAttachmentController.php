<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attachments;

use App\Domain\Attachments\Actions\DeleteAttachment;
use App\Domain\Attachments\Models\Attachment;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteAttachmentController
{
    public function __invoke(Request $request, int $attachment, DeleteAttachment $action): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa.');
        }

        $model = Attachment::query()->findOrFail($attachment);

        $action->execute($user, $company, $model);

        return back()->with('status', 'Anexo excluído.');
    }
}
