@extends('layouts.app')

@section('title', 'Editar empresa | Frotika')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Editar empresa" subtitle="{{ $company->getAttribute('trade_name') }}">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('companies.show', ['company' => $company->getKey()]) }}"
                    variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <form method="POST" action="{{ route('companies.update', ['company' => $company->getKey()]) }}">
                @csrf
                @method('PUT')
                @include('companies._form', ['company' => $company])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('companies.show', ['company' => $company->getKey()]) }}"
                        variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
