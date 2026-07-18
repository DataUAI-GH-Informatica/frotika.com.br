@extends('layouts.app')

@section('title', 'Novo abastecimento | Frotika')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Novo abastecimento" subtitle="Lance o abastecimento; a despesa entra no fluxo de caixa automaticamente.">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('fuelings.index') }}" variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <form method="POST" action="{{ route('fuelings.store') }}">
                @csrf
                @include('fuelings._form', ['fueling' => null])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('fuelings.index') }}" variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Lançar abastecimento</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
