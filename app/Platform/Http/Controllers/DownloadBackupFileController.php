<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Platform\Support\BackupStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadBackupFileController
{
    public function __invoke(Request $request, BackupStorage $backupStorage): BinaryFileResponse
    {
        $fileName = (string) $request->query('file', '');
        $relativePath = $backupStorage->resolvePath($fileName);

        if ($relativePath === null) {
            abort(404);
        }

        return response()->download($backupStorage->absolutePath($relativePath), basename($relativePath));
    }
}
