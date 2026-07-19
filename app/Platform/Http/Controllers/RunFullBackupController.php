<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

final class RunFullBackupController
{
    public function __invoke(): RedirectResponse
    {
        $exitCode = Artisan::call('backup:run');

        if ($exitCode !== 0) {
            return redirect()
                ->route('platform.backups.index')
                ->with('warning', 'Falha ao executar backup completo. Verifique os logs da aplicacao.');
        }

        return redirect()
            ->route('platform.backups.index')
            ->with('status', 'Backup completo executado com sucesso.');
    }
}
