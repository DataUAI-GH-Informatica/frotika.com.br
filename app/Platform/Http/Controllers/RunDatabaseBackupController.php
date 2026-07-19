<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

final class RunDatabaseBackupController
{
    public function __invoke(): RedirectResponse
    {
        $exitCode = Artisan::call('backup:run', ['--only-db' => true]);

        if ($exitCode !== 0) {
            return redirect()
                ->route('platform.backups.index')
                ->with('warning', 'Falha ao executar backup do banco. Verifique os logs da aplicacao.');
        }

        return redirect()
            ->route('platform.backups.index')
            ->with('status', 'Backup de banco executado com sucesso.');
    }
}
