@extends('layouts.app')

@section('title', 'Editar abastecimento | Frotika')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Editar abastecimento" subtitle="Alterar o valor ou o produto recalcula o consumo e a despesa.">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('fuelings.show', ['fueling' => $fueling->getKey()]) }}" variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <form method="POST" action="{{ route('fuelings.update', ['fueling' => $fueling->getKey()]) }}">
                @csrf
                @method('PUT')
                @include('fuelings._form', ['fueling' => $fueling])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('fuelings.show', ['fueling' => $fueling->getKey()]) }}" variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
