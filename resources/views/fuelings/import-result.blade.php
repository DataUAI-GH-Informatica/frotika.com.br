@extends('layouts.app')

@section('title', 'Importação de abastecimentos | Frotika')

@php
    use App\Domain\Fuelings\Enums\FuelingImportItemStatus;

    $results = $batch->results ?? [];
    $completed = $batch->isCompleted();
@endphp

@section('content')
    <div id="fueling-import-result" data-uuid="{{ $batch->uuid }}" data-status="{{ $batch->status->value }}">
        <x-ui.page-header title="Importação de abastecimentos"
            subtitle="{{ $batch->original_name }} · enviada em {{ Format::dateTime($batch->created_at) }}">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('fuelings.import') }}" variant="secondary" size="sm">Nova importação</x-ui.link-button>
                <x-ui.link-button href="{{ route('fuelings.index') }}" variant="ghost" size="sm">Ver abastecimentos</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Faixa de instrumentos: cor só onde ela codifica um fato (linha com erro). --}}
        <section class="rounded-lg border border-slate-200 bg-white">
            <dl class="grid grid-cols-2 divide-slate-200 md:grid-cols-5 md:divide-x">
                <div class="border-b border-slate-200 p-4 md:border-b-0">
                    <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Linhas</dt>
                    <dd class="mt-1 font-display text-2xl font-bold tabular text-slate-900">{{ $batch->total_rows }}</dd>
                    <p class="mt-1 text-xs text-slate-400">{{ $batch->processed_rows }} processadas</p>
                </div>
                <div class="border-b border-slate-200 p-4 md:border-b-0">
                    <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Importadas</dt>
                    <dd class="mt-1 font-display text-2xl font-bold tabular text-slate-900">{{ $batch->imported_count }}</dd>
                    <p class="mt-1 text-xs text-slate-400">abastecimentos criados</p>
                </div>
                <div class="border-b border-slate-200 p-4 md:border-b-0">
                    <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Ignoradas</dt>
                    <dd class="mt-1 font-display text-2xl font-bold tabular text-slate-900">{{ $batch->ignored_count }}</dd>
                    <p class="mt-1 text-xs text-slate-400">já existiam</p>
                </div>
                <div class="border-b border-slate-200 p-4 md:border-b-0">
                    <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Com erro</dt>
                    <dd @class([
                        'mt-1 font-display text-2xl font-bold tabular',
                        'text-danger-700' => $batch->failed_count > 0,
                        'text-slate-900' => $batch->failed_count === 0,
                    ])>{{ $batch->failed_count }}</dd>
                    <p class="mt-1 text-xs text-slate-400">linhas a corrigir</p>
                </div>
                <div class="p-4">
                    <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Situação</dt>
                    <dd class="mt-1 font-display text-lg font-semibold text-slate-900">{{ $batch->status->label() }}</dd>
                    <p class="mt-1 text-xs text-slate-400" data-fueling-import-hint>
                        {{ $completed ? 'Processamento concluído' : 'Processando em segundo plano…' }}
                    </p>
                </div>
            </dl>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-4 py-2.5">
                <h2 class="font-display text-lg font-semibold text-slate-900">Linhas da planilha</h2>
                <p class="text-xs text-slate-400">O número da linha é o da planilha que você enviou</p>
            </div>

            @if ($results === [])
                <div class="px-4 py-12 text-center">
                    <p class="font-display text-lg font-semibold text-slate-900">Aguardando processamento.</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                        A planilha entrou na fila. O resultado de cada linha aparece aqui conforme é processada.
                    </p>
                </div>
            @else
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr class="border-b border-slate-200">
                                <th class="w-16 px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Linha</th>
                                <th class="w-28 px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Veículo</th>
                                <th class="w-28 px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Situação</th>
                                <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Detalhe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $item)
                                @php
                                    $status = FuelingImportItemStatus::tryFrom($item['status'] ?? '');
                                    $number = (int) ($item['row'] ?? 0);
                                @endphp
                                <tr class="h-9 border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-3 text-right font-mono text-xs tabular text-slate-500">
                                        {{ $number > 0 ? $number : '—' }}
                                    </td>
                                    <td class="px-3 font-mono text-xs text-slate-700">
                                        {{ ! empty($item['plate']) ? Format::plate($item['plate']) : '—' }}
                                    </td>
                                    <td class="px-3">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-success-50 text-success-700' => $status === FuelingImportItemStatus::Imported,
                                            'bg-slate-100 text-slate-600' => $status === FuelingImportItemStatus::Ignored,
                                            'bg-danger-50 text-danger-700' => $status === FuelingImportItemStatus::Failed,
                                        ])>{{ $status?->label() ?? 'Desconhecida' }}</span>
                                    </td>
                                    <td class="px-3 py-1.5">
                                        @if (! empty($item['fueling_id']))
                                            <a href="{{ route('fuelings.show', ['fueling' => $item['fueling_id']]) }}"
                                                class="inline-flex flex-wrap items-center gap-2 text-brand-700 hover:underline">
                                                <span class="text-sm">{{ $status === FuelingImportItemStatus::Imported ? 'Ver abastecimento' : 'Ver o que já existia' }}</span>
                                                @if (! empty($item['code']))
                                                    <span class="font-mono text-2xs text-slate-400">{{ $item['code'] }}</span>
                                                @endif
                                            </a>
                                        @elseif ($status === FuelingImportItemStatus::Failed)
                                            <span class="text-xs text-danger-700">{{ $item['message'] ?? 'Não foi possível importar esta linha.' }}</span>
                                        @else
                                            <span class="text-xs text-slate-500">{{ $item['message'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
