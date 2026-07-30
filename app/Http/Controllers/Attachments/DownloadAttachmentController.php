<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attachments;

use App\Domain\Attachments\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadAttachmentController
{
    public function __invoke(Request $request, int $attachment): StreamedResponse
    {
        $model = Attachment::query()->findOrFail($attachment);

        Gate::authorize('view', $model);

        if (! Storage::disk($model->disk)->exists($model->path)) {
            abort(404);
        }

        return Storage::disk($model->disk)->download(
            $model->path,
            $model->original_name,
            ['Content-Type' => $model->mime],
        );
    }
}
