<?php

declare(strict_types=1);

namespace App\Platform\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BackupStorage
{
    public function diskName(): string
    {
        $diskName = (string) config('backup.backup.destination.disks.0', 'backups');

        return $diskName !== '' ? $diskName : 'backups';
    }

    public function appDirectory(): string
    {
        $appName = (string) config('backup.backup.name', config('app.name', 'laravel-backup'));

        return trim($appName, '/');
    }

    /**
     * @return array<int, array{file_name: string, relative_path: string, size_bytes: int, last_modified: int}>
     */
    public function files(): array
    {
        $disk = Storage::disk($this->diskName());
        $directory = $this->appDirectory();

        if (! $disk->exists($directory)) {
            return [];
        }

        $rows = [];

        foreach ($disk->files($directory) as $path) {
            if (! $disk->exists($path)) {
                continue;
            }

            $rows[] = [
                'file_name' => basename($path),
                'relative_path' => $path,
                'size_bytes' => (int) $disk->size($path),
                'last_modified' => (int) $disk->lastModified($path),
            ];
        }

        usort($rows, static fn (array $left, array $right): int => $right['last_modified'] <=> $left['last_modified']);

        return $rows;
    }

    public function resolvePath(string $fileName): ?string
    {
        $safeFileName = trim($fileName);

        if ($safeFileName === '') {
            return null;
        }

        if (basename($safeFileName) !== $safeFileName) {
            return null;
        }

        if (Str::contains($safeFileName, ['/', '\\'])) {
            return null;
        }

        $relativePath = $this->appDirectory().'/'.$safeFileName;

        return Storage::disk($this->diskName())->exists($relativePath) ? $relativePath : null;
    }

    public function absolutePath(string $relativePath): string
    {
        $disk = Storage::disk($this->diskName());

        return $disk->path($relativePath);
    }
}
