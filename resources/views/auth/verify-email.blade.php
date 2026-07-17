@extends('layouts.guest')

@section('title', 'Confirmar e-mail | Frotika')

@section('content')
    <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1fr_1.1fr] lg:items-start">
        <section class="relative overflow-hidden rounded-lg border border-brand-800 bg-brand-950 p-6 sm:p-8">
            <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-lg border border-brand-700/50"></div>

            <p
                class="inline-flex items-center rounded-md bg-accent-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-300">
                Acesso ao painel
            </p>
            <h1 class="mt-4 font-display text-3xl font-semibold text-white sm:text-4xl">Confirme seu e-mail</h1>
            <p class="mt-3 max-w-lg text-sm text-brand-100/95 sm:text-base">
                Enviamos um link de confirmacao para seu e-mail. Abra o link para liberar o acesso ao painel.
            </p>
        </section>

        <x-ui.card class="mx-auto w-full max-w-md border-slate-300 bg-white">
            <h2 class="font-display text-xl font-semibold text-slate-900">Quase pronto</h2>
            <p class="mt-2 text-sm text-slate-600">
                Se nao encontrou o e-mail, solicite um novo link de confirmacao abaixo.
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf

                <x-ui.button type="submit" class="w-full justify-center">
                    Reenviar e-mail de confirmacao
                </x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf

                <x-ui.button type="submit" variant="secondary" class="w-full justify-center">
                    Sair desta conta
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
