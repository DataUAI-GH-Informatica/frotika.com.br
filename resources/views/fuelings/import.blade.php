@extends('layouts.app')

@section('title', 'Importar abastecimentos | Frotika')

@section('content')
    <x-ui.page-header title="Importar abastecimentos"
        subtitle="Uma linha por abastecimento. O Frotika processa em segundo plano e avisa você quando terminar." />

    <div class="mx-auto max-w-2xl">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <form method="POST" action="{{ route('fuelings.import.store') }}" enctype="multipart/form-data"
                class="space-y-4" data-fueling-import>
                @csrf

                <div>
                    <label for="sheet" class="mb-1 block text-sm font-medium text-slate-700">Planilha de abastecimentos</label>
                    <input type="file" name="sheet" id="sheet" accept=".xlsx" required data-fueling-import-input
                        class="block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />
                    @error('sheet')
                        <p class="mt-1 text-xs text-danger-700">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-slate-500">
                        Arquivo .xlsx de até 4 MB e {{ $maxRows }} linhas. O cabeçalho da primeira linha é reconhecido pelo
                        nome da coluna, então a ordem não importa.
                    </p>
                </div>

                <div data-fueling-import-summary hidden>
                    <div class="flex items-center justify-between gap-3 border-y border-slate-100 py-1.5">
                        <span class="truncate font-mono text-xs text-slate-700" data-fueling-import-name></span>
                        <span class="shrink-0 font-mono text-2xs tabular text-slate-400" data-fueling-import-size></span>
                    </div>
                    <p data-fueling-import-warning hidden class="mt-2 text-xs text-danger-700">
                        Só planilha .xlsx. Se o seu arquivo é .xls ou .csv, abra e salve como .xlsx.
                    </p>
                </div>

                <dl class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs">
                    <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Colunas obrigatórias</dt>
                    <dd class="mt-1 font-mono text-slate-700">{{ implode(' · ', $requiredColumns) }}</dd>
                    <dt class="mt-2.5 text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Como funciona</dt>
                    <dd class="mt-1 space-y-1 text-slate-600">
                        <p>O veículo é encontrado pela placa e o motorista pelo CPF — os dois precisam já estar cadastrados.</p>
                        <p>O posto é cadastrado sozinho quando o CNPJ ainda não existe na sua base.</p>
                        <p>Reenviar a mesma planilha não duplica nada: a linha repetida é ignorada.</p>
                    </dd>
                </dl>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                    <x-ui.link-button href="{{ route('fuelings.import.template') }}" variant="ghost" size="sm">
                        Baixar planilha modelo
                    </x-ui.link-button>
                    <div class="flex items-center gap-2">
                        <x-ui.link-button href="{{ route('fuelings.index') }}" variant="secondary">Cancelar</x-ui.link-button>
                        <x-ui.button type="submit" variant="primary" data-fueling-import-submit>Importar</x-ui.button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
