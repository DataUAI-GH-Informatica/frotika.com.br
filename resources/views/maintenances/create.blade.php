@extends('layouts.app')

@section('title', 'Nova manutenção | Frotika')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Nova manutenção" subtitle="Registre o serviço; o custo entra no financeiro como conta a pagar.">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('maintenances.index') }}" variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <form method="POST" action="{{ route('maintenances.store') }}">
                @csrf
                @include('maintenances._form', ['maintenance' => null])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('maintenances.index') }}" variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Lançar manutenção</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
