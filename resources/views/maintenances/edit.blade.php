@extends('layouts.app')

@section('title', 'Editar manutenção | Frotika')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Editar manutenção" subtitle="Alterar custos ou tipo recalcula a despesa vinculada.">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('maintenances.show', ['maintenance' => $maintenance->getKey()]) }}" variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <form method="POST" action="{{ route('maintenances.update', ['maintenance' => $maintenance->getKey()]) }}">
                @csrf
                @method('PUT')
                @include('maintenances._form', ['maintenance' => $maintenance])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('maintenances.show', ['maintenance' => $maintenance->getKey()]) }}" variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
