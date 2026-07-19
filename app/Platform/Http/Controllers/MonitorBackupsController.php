<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

final class MonitorBackupsController
{
    public function __invoke(): RedirectResponse
    {
        $exitCode = Artisan::call('backup:monitor');

        if ($exitCode !== 0) {
            return redirect()
                ->route('platform.backups.index')
                ->with('warning', 'Monitoramento identificou alerta de saude dos backups. Verifique os logs e notificacoes.');
        }

        Cache::put('platform.backups.last_monitor_run_at', now()->toDateTimeString());

        return redirect()
            ->route('platform.backups.index')
            ->with('status', 'Monitoramento de backups executado com sucesso.');
    }
}
