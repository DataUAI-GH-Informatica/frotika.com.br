<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Actions;

use App\Domain\Attachments\Models\Attachment;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class DeleteAttachment
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    public function execute(User $actor, Company $company, Attachment $attachment): void
    {
        Gate::forUser($actor)->authorize('delete', $attachment);

        $this->tenant->runFor($company, function () use ($attachment): void {
            Storage::disk($attachment->disk)->delete($attachment->path);

            $attachment->delete();
        });
    }
}
