<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Platform\Http\Requests\DeleteBackupFileRequest;
use App\Platform\Support\BackupStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

final class DeleteBackupFileController
{
    public function __invoke(DeleteBackupFileRequest $request, BackupStorage $backupStorage): RedirectResponse
    {
        $fileName = (string) $request->validated('file');
        $relativePath = $backupStorage->resolvePath($fileName);

        if ($relativePath === null) {
            return redirect()
                ->route('platform.backups.index')
                ->with('warning', 'Arquivo de backup nao encontrado para exclusao.');
        }

        Storage::disk($backupStorage->diskName())->delete($relativePath);

        return redirect()
            ->route('platform.backups.index')
            ->with('status', 'Arquivo de backup excluido com sucesso.');
    }
}
