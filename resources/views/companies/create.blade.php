@extends('layouts.app')

@section('title', 'Nova empresa | Frotika')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Nova empresa" subtitle="Cadastre um novo CNPJ no grupo">
            <x-slot:actions>
                <x-ui.link-button href="{{ route('companies.index') }}" variant="secondary">Voltar</x-ui.link-button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card class="border-slate-200 bg-white">
            <p class="text-sm text-slate-600">
                Informe o CNPJ e buscamos a razão social e o endereço na Receita. A empresa nasce com o plano de contas
                base e a conta Caixa. A licença é do grupo — cadastrar uma empresa não gera cobrança extra.
            </p>

            <form method="POST" action="{{ route('companies.store') }}" class="mt-6">
                @csrf
                @include('companies._form', ['company' => null])

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-ui.link-button href="{{ route('companies.index') }}" variant="secondary">Cancelar</x-ui.link-button>
                    <x-ui.button type="submit">Cadastrar empresa</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
