<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fuelings\Import\FuelingSheetTemplate;
use App\Domain\Fuelings\Models\Fueling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadFuelingImportTemplateController
{
    public function __invoke(Request $request, FuelingSheetTemplate $template): StreamedResponse
    {
        Gate::authorize('create', Fueling::class);

        $contents = $template->build();

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            'frotika-modelo-abastecimentos.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Length' => (string) strlen($contents),
            ],
        );
    }
}
