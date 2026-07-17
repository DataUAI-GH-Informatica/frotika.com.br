@extends('layouts.guest')

@section('title', 'Entrar | Frotika')

@section('content')
    <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1fr_1.1fr] lg:items-start">
        <section class="relative overflow-hidden rounded-lg border border-brand-800 bg-brand-950 p-6 sm:p-8">
            <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-lg border border-brand-700/50">
            </div>

            <p
                class="inline-flex items-center rounded-md bg-accent-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-300">
                DRE Veicular em minutos
            </p>
            <h1 class="mt-4 font-display text-3xl font-semibold text-white sm:text-4xl">Entrar no painel da empresa</h1>
            <p class="mt-3 max-w-lg text-sm text-brand-100/95 sm:text-base">
                Receita, custo e resultado por veiculo em um fluxo diario simples para quem toca a operacao.
            </p>

            <x-ui.card class="mt-5 border-brand-700/40 bg-brand-900/50 text-brand-100">
                <p class="text-sm">
                    Use o mesmo acesso para lancamentos, fluxo de caixa e DRE veicular.
                </p>
            </x-ui.card>
        </section>

        <x-ui.card class="mx-auto w-full max-w-md border-slate-300 bg-white">
            <h2 class="font-display text-xl font-semibold text-slate-900">Acesso da empresa</h2>
            <p class="mt-2 text-sm text-slate-600">Use seu e-mail e senha para abrir o painel.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
                @csrf

                <x-ui.input label="E-mail" name="email" type="email" placeholder="voce@empresa.com.br"
                    autocomplete="email" required />

                <x-ui.input label="Senha" name="password" type="password" placeholder="Sua senha"
                    autocomplete="current-password" required />

                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"
                        class="h-4 w-4 rounded-[4px] border-slate-300 text-brand-700 focus:ring-brand-500/30"
                        @checked(old('remember')) />
                    Permanecer conectado
                </label>

                <p class="text-right text-sm">
                    <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:text-brand-800">
                        Esqueci minha senha
                    </a>
                </p>

                <x-ui.button type="submit" class="w-full justify-center">
                    Entrar
                </x-ui.button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                Ainda nao tem acesso?
                <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:text-brand-800">Criar conta
                    agora</a>
            </p>
        </x-ui.card>
    </div>
@endsection
