<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Platform\Support\BackupStorage;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

final class ListBackupsController
{
    public function __invoke(BackupStorage $backupStorage): View
    {
        $rawFiles = $backupStorage->files();

        $files = array_map(static function (array $file): array {
            $lastModified = CarbonImmutable::createFromTimestamp($file['last_modified']);

            return [
                'file_name' => $file['file_name'],
                'size_human' => self::formatBytes((int) $file['size_bytes']),
                'size_bytes' => (int) $file['size_bytes'],
                'last_modified_label' => Format::dateTime($lastModified),
            ];
        }, $rawFiles);

        $totalSizeBytes = (int) array_sum(array_column($rawFiles, 'size_bytes'));
        $lastBackupTimestamp = $rawFiles[0]['last_modified'] ?? null;
        $lastMonitorRunAt = Cache::get('platform.backups.last_monitor_run_at');

        return view('platform.backups.index', [
            'files' => $files,
            'backupDisk' => $backupStorage->diskName(),
            'backupDirectory' => 'storage/app/'.$backupStorage->diskName().'/'.$backupStorage->appDirectory(),
            'totalFiles' => count($files),
            'totalSizeLabel' => self::formatBytes($totalSizeBytes),
            'lastBackupLabel' => $lastBackupTimestamp !== null
                ? Format::dateTime(CarbonImmutable::createFromTimestamp((int) $lastBackupTimestamp))
                : null,
            'lastMonitorLabel' => is_string($lastMonitorRunAt) && $lastMonitorRunAt !== ''
                ? Format::dateTime($lastMonitorRunAt)
                : null,
        ]);
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $index => $unit) {
            if ($value < 1024 || $index === array_key_last($units)) {
                return number_format($value, 2, ',', '.').' '.$unit;
            }

            $value /= 1024;
        }

        return number_format($value, 2, ',', '.').' TB';
    }
}
