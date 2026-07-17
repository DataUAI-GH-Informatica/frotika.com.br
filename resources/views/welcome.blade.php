@extends('layouts.guest')

@section('title', 'Frotika | DRE Veicular para transportadoras')

@section('content')
    <section class="grid gap-6 lg:grid-cols-[1.2fr_1fr] lg:items-stretch">
        <div class="relative overflow-hidden rounded-lg border border-brand-800 bg-brand-950 p-6 sm:p-8">
            <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-lg border border-brand-700/50">
            </div>
            <div class="pointer-events-none absolute -bottom-12 left-10 h-24 w-24 rounded-lg border border-brand-800/60">
            </div>

            <p
                class="inline-flex items-center rounded-md bg-accent-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-300">
                Sistema para micro transportadoras
            </p>

            <h1 class="mt-4 max-w-3xl font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                Saiba hoje se cada caminhao esta dando lucro.
            </h1>

            <p class="mt-4 max-w-2xl text-base text-brand-100/95">
                O Frotika transforma CT-e, abastecimento e manutencao em decisao financeira por veiculo. Menos
                adivinhacao, mais margem controlada na rotina real da transportadora.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-ui.link-button href="{{ route('register') }}">
                    Criar conta e comecar
                </x-ui.link-button>
                <x-ui.link-button href="{{ route('login') }}" variant="secondary">
                    Ja tenho acesso
                </x-ui.link-button>
            </div>
        </div>

        <x-ui.card class="border-slate-300 bg-white">
            <h2 class="font-display text-xl font-semibold text-slate-900">Resumo da operacao em um painel</h2>
            <p class="mt-2 text-sm text-slate-600">
                A tela principal mostra o resultado por veiculo e o que exige acao agora.
            </p>

            <div class="mt-4 rounded-md border border-slate-300 bg-slate-50 p-3">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <x-ui.plate-chip plate="RIO2A18" type="tractor" />
                    <p class="font-mono text-sm text-success-700 tabular">+<span class="unit">R$</span> 2.120,00</p>
                </div>

                <div class="space-y-2 pt-3">
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                            <span>Custo/km</span>
                            <span class="font-mono tabular">3,95</span>
                        </div>
                        <div class="h-2 rounded-md bg-slate-200">
                            <div class="h-2 w-[72%] rounded-md bg-danger-500"></div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                            <span>Receita/km</span>
                            <span class="font-mono tabular">4,37</span>
                        </div>
                        <div class="h-2 rounded-md bg-slate-200">
                            <div class="h-2 w-[80%] rounded-md bg-success-500"></div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-500">R$ por km com margem positiva ou negativa sem abrir planilha.</p>
        </x-ui.card>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        <x-ui.card class="border-slate-300 bg-white">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Importacao</p>
            <h3 class="mt-2 font-display text-xl font-semibold text-slate-900">CT-e para dentro em lote</h3>
            <p class="mt-2 text-sm text-slate-600">
                Suba varios XMLs e gere viagens sem recadastro manual de receita.
            </p>
        </x-ui.card>

        <x-ui.card class="border-slate-300 bg-white">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Controle</p>
            <h3 class="mt-2 font-display text-xl font-semibold text-slate-900">Fluxo de caixa de verdade</h3>
            <p class="mt-2 text-sm text-slate-600">
                Realizado e previsto na mesma visao para antecipar falta de caixa.
            </p>
        </x-ui.card>

        <x-ui.card class="border-slate-300 bg-white">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resultado</p>
            <h3 class="mt-2 font-display text-xl font-semibold text-slate-900">DRE por veiculo, sem misterio</h3>
            <p class="mt-2 text-sm text-slate-600">
                Veja custo, receita e margem por caminhao para decidir com seguranca.
            </p>
        </x-ui.card>
    </section>
@endsection
