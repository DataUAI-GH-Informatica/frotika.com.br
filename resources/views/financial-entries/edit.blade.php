@extends('layouts.app')

@section('title', 'Editar lançamento | Frotika')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-ui.page-header title="Editar lançamento" :subtitle="$entry->getAttribute('description')">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('financial-entries.show', ['entry' => $entry->getKey()]) }}" variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <form method="POST" action="{{ route('financial-entries.update', ['entry' => $entry->getKey()]) }}">
                @csrf
                @method('PUT')

                @if ($entry->getAttribute('recurrence_id') !== null)
                    @php
                        $applyScopeVal = old('apply_scope', 'single');
                    @endphp
                    <div class="mb-4 rounded-md border border-slate-200 bg-slate-50 p-3">
                        <span class="text-sm font-medium text-slate-700">Aplicar alterações em</span>
                        <div class="mt-2 grid gap-2 sm:grid-cols-3" role="radiogroup">
                            <label>
                                <input type="radio" name="apply_scope" value="single" class="peer sr-only" @checked($applyScopeVal === 'single') />
                                <span class="flex h-9 cursor-pointer items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-slate-600 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:font-medium peer-checked:text-brand-700">Somente este</span>
                            </label>
                            <label>
                                <input type="radio" name="apply_scope" value="forward" class="peer sr-only" @checked($applyScopeVal === 'forward') />
                                <span class="flex h-9 cursor-pointer items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-slate-600 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:font-medium peer-checked:text-brand-700">Daqui em diante</span>
                            </label>
                            <label>
                                <input type="radio" name="apply_scope" value="all" class="peer sr-only" @checked($applyScopeVal === 'all') />
                                <span class="flex h-9 cursor-pointer items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-slate-600 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:font-medium peer-checked:text-brand-700">Toda a série</span>
                            </label>
                        </div>
                    </div>
                @endif

                @include('financial-entries._form', ['entry' => $entry])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('financial-entries.show', ['entry' => $entry->getKey()]) }}" variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
