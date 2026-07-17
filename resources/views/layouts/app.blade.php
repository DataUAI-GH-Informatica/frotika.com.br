<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>@yield('title', 'Painel | Frotika')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $topbarCompanies = $topbarCompanies ?? collect();
        $topbarCurrentCompanyId = $topbarCurrentCompanyId ?? null;
        $topbarCurrentCompanyName = $topbarCurrentCompanyName ?? 'Empresa ativa';
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[var(--spacing-sidebar)_1fr]">
        <aside
            class="hidden lg:flex lg:flex-col lg:justify-between lg:bg-linear-to-b lg:from-brand-950 lg:to-brand-900 lg:px-3 lg:py-4 lg:text-brand-100">
            <div class="space-y-6">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-brand-800/60">
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-accent-500 font-display text-sm font-semibold text-brand-950">F</span>
                    <span class="font-display text-base font-semibold text-white">Frotika</span>
                </a>

                <nav class="space-y-4">
                    <section>
                        <p class="px-2 text-2xs font-semibold uppercase tracking-[0.18em] text-brand-300">Operacao</p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="{{ route('dashboard') }}"
                                    class="block rounded-md border-l-2 border-accent-500 bg-brand-800/70 px-2 py-1.5 text-sm font-medium text-white">
                                    Painel
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Viagens
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Abastecimentos
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Manutencoes
                                </a>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <p class="px-2 text-2xs font-semibold uppercase tracking-[0.18em] text-brand-300">Frota</p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Veiculos
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Motoristas
                                </a>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <p class="px-2 text-2xs font-semibold uppercase tracking-[0.18em] text-brand-300">Financeiro
                        </p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Lancamentos
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Fluxo de caixa
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    Contas bancarias
                                </a>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <p class="px-2 text-2xs font-semibold uppercase tracking-[0.18em] text-brand-300">Analise</p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="#"
                                    class="block rounded-md px-2 py-1.5 text-sm text-brand-100 hover:bg-brand-800/60">
                                    DRE veicular
                                </a>
                            </li>
                        </ul>
                    </section>
                </nav>
            </div>

            <div class="border-t border-brand-800/80 pt-3">
                <p class="px-2 text-xs text-brand-300">{{ $topbarCurrentCompanyName }}</p>

                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <x-ui.button type="submit" variant="ghost" size="sm"
                        class="w-full justify-start text-brand-100 hover:bg-brand-800/60 active:bg-brand-800/80">
                        Sair
                    </x-ui.button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen flex-col">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white safe-t">
                <div class="flex h-(--spacing-topbar) items-center gap-3 px-4 sm:px-6">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-md px-2 py-1.5 text-brand-900 hover:bg-slate-100 lg:hidden">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-brand-900 text-xs font-semibold text-white">F</span>
                        <span class="font-display text-sm font-semibold">Frotika</span>
                    </a>

                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div>
                            <p class="text-2xs font-semibold uppercase tracking-[0.14em] text-slate-500">Empresa ativa</p>

                            @if ($topbarCompanies->count() > 1)
                                <form method="POST" action="{{ route('tenancy.switch-company') }}"
                                    class="mt-0.5 flex items-center gap-2">
                                    @csrf
                                    <select
                                        name="company_id"
                                        class="h-8 min-w-56 rounded-md border border-slate-300 bg-white px-2.5 text-sm text-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                                        onchange="this.form.requestSubmit()">
                                        @foreach ($topbarCompanies as $companyOption)
                                            <option value="{{ $companyOption->getKey() }}"
                                                @selected((int) $topbarCurrentCompanyId === $companyOption->getKey())>
                                                {{ $companyOption->getAttribute('trade_name') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <x-ui.button type="submit" variant="secondary" size="sm" class="sm:hidden">
                                        Trocar
                                    </x-ui.button>
                                </form>
                            @else
                                <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $topbarCurrentCompanyName }}</p>
                            @endif
                        </div>

                        <label class="relative ml-auto hidden min-w-72 flex-1 items-center md:flex">
                            <span class="pointer-events-none absolute left-3 text-xs uppercase tracking-widest text-slate-400">Buscar</span>
                            <input type="text" placeholder="Placa, motorista, CT-e"
                                class="h-9 w-full rounded-md border border-slate-300 bg-white px-20 pr-14 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />
                            <span
                                class="pointer-events-none absolute right-2 rounded border border-slate-300 px-1.5 py-0.5 font-mono text-2xs text-slate-500 tabular">Ctrl+K</span>
                        </label>
                    </div>

                    <div class="hidden items-center gap-2 xl:flex">
                        <x-ui.button variant="ghost" size="sm">+ Viagem</x-ui.button>
                        <x-ui.button variant="ghost" size="sm">+ Abastecimento</x-ui.button>
                        <x-ui.button variant="ghost" size="sm">+ Manutencao</x-ui.button>
                        <x-ui.button variant="ghost" size="sm">Importar CT-e</x-ui.button>
                    </div>

                    <a href="{{ route('welcome') }}"
                        class="inline-flex h-9 items-center rounded-md border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Site
                    </a>
                </div>
            </header>

            <main class="flex-1 px-4 pb-24 pt-5 sm:px-6 lg:px-6 lg:pb-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md border border-success-500/40 bg-success-50 px-4 py-2.5 text-sm font-medium text-success-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mx-auto w-full">
                    @yield('content')
                </div>
            </main>

            <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white lg:hidden safe-b">
                <div class="grid h-(--spacing-bottomnav) grid-cols-5 px-2">
                    <a href="{{ route('dashboard') }}"
                        class="flex flex-col items-center justify-center text-2xs font-medium text-brand-700">
                        <span class="font-mono text-sm">01</span>
                        Inicio
                    </a>
                    <a href="#" class="flex flex-col items-center justify-center text-2xs text-slate-500">
                        <span class="font-mono text-sm">02</span>
                        Viagens
                    </a>
                    <button type="button" aria-label="Novo lancamento" class="flex items-center justify-center">
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-700 font-mono text-lg font-semibold text-white">+</span>
                    </button>
                    <a href="#" class="flex flex-col items-center justify-center text-2xs text-slate-500">
                        <span class="font-mono text-sm">03</span>
                        Frota
                    </a>
                    <button type="button" class="flex flex-col items-center justify-center text-2xs text-slate-500">
                        <span class="font-mono text-sm">04</span>
                        Mais
                    </button>
                </div>
            </nav>
        </div>
    </div>
</body>

</html>
