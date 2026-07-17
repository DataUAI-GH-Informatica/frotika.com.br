@extends('layouts.guest')

@section('title', 'Criar conta | Frotika')

@section('content')
    <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[0.95fr_1.4fr]">
        <section class="relative overflow-hidden rounded-lg border border-brand-800 bg-brand-950 p-6 sm:p-8">
            <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-lg border border-brand-700/50"></div>

            <p
                class="inline-flex items-center rounded-md bg-accent-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-300">
                Onboarding inicial
            </p>
            <h1 class="mt-4 font-display text-3xl font-semibold text-white sm:text-4xl">Comece seu DRE Veicular</h1>
            <p class="mt-3 text-sm text-brand-100/95 sm:text-base">
                Cadastre seu usuario e sua empresa para ativar o ambiente inicial do Frotika.
            </p>

            <x-ui.card class="mt-5 border-brand-700/40 bg-brand-900/50 text-brand-100">
                <p class="text-sm">
                    O cadastro ja cria a conta Caixa, assinatura trial e plano de contas base para iniciar rapido.
                </p>
            </x-ui.card>
        </section>

        <x-ui.card class="border-slate-300 bg-white">
            <h2 class="font-display text-xl font-semibold text-slate-900">Criar conta da transportadora</h2>
            <p class="mt-2 text-sm text-slate-600">Preencha os dados abaixo para liberar o painel.</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <x-ui.input label="Nome" name="name" placeholder="Nome do responsavel" autocomplete="name"
                        required />
                </div>

                <x-ui.input label="E-mail" name="email" type="email" placeholder="voce@empresa.com.br"
                    autocomplete="email" required />

                <x-ui.input label="Senha" name="password" type="password" placeholder="No minimo 8 caracteres"
                    autocomplete="new-password" required />

                <div class="sm:col-span-2">
                    <x-ui.input label="Nome do grupo" name="group_name" placeholder="Grupo da transportadora" required />
                </div>

                <x-ui.input label="Razao social" name="company_legal_name" placeholder="Empresa de Transportes LTDA"
                    required />

                <x-ui.input label="Nome fantasia" name="company_trade_name" placeholder="Transportes Exemplo" required />

                <x-ui.input label="CNPJ" name="company_cnpj" placeholder="00000000000000" autocomplete="off" required />

                <x-ui.select label="Regime tributario" name="tax_regime" required>
                    <option value="simples" @selected(old('tax_regime', 'simples') === 'simples')>Simples Nacional</option>
                    <option value="presumido" @selected(old('tax_regime') === 'presumido')>Lucro Presumido</option>
                    <option value="real" @selected(old('tax_regime') === 'real')>Lucro Real</option>
                </x-ui.select>

                <div class="sm:col-span-2 mt-2 flex flex-wrap items-center justify-end gap-3">
                    <x-ui.link-button href="{{ route('login') }}" variant="secondary">
                        Ja tenho conta
                    </x-ui.link-button>

                    <x-ui.button type="submit">
                        Criar conta
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
