<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

final class CleanBackupsController
{
    public function __invoke(): RedirectResponse
    {
        $exitCode = Artisan::call('backup:clean');

        if ($exitCode !== 0) {
            return redirect()
                ->route('platform.backups.index')
                ->with('warning', 'Falha ao limpar backups antigos. Verifique os logs da aplicacao.');
        }

        return redirect()
            ->route('platform.backups.index')
            ->with('status', 'Limpeza de backups executada com sucesso.');
    }
}
