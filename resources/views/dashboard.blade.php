@extends('layouts.app')

@section('title', 'Painel | Frotika')

@section('content')
    <x-ui.page-header title="Painel operacional" subtitle="Julho de 2026 · consolidado da frota">
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm">Importar CT-e</x-ui.button>
            <x-ui.button size="sm">Nova viagem</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Faixa de instrumentos: um único card, separado por filete. Sem 4 cards
         coloridos. Monocromático — só o resultado ganha sinal, não cor. --}}
    <section class="rounded-lg border border-slate-200 bg-white">
        <dl class="grid grid-cols-2 divide-slate-200 md:grid-cols-4 md:divide-x">
            <div class="border-b border-slate-200 p-4 md:border-b-0">
                <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Saldo consolidado</dt>
                <dd class="mt-1 font-display text-2xl font-bold text-slate-900 tabular">
                    <span class="unit">R$</span> {{ Format::moneyDecimal(128420.55) }}
                </dd>
                <p class="mt-1 text-xs text-slate-400">Caixa + bancos</p>
            </div>
            <div class="border-b border-slate-200 p-4 md:border-b-0">
                <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Receita do mês</dt>
                <dd class="mt-1 font-display text-2xl font-bold text-slate-900 tabular">
                    <span class="unit">R$</span> {{ Format::moneyDecimal(214973.10) }}
                </dd>
                <p class="mt-1 text-xs text-slate-400">Competência atual</p>
            </div>
            <div class="border-b border-slate-200 p-4 md:border-b-0">
                <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Custos do mês</dt>
                <dd class="mt-1 font-display text-2xl font-bold text-slate-900 tabular">
                    <span class="unit">R$</span> {{ Format::moneyDecimal(189441.67) }}
                </dd>
                <p class="mt-1 text-xs text-slate-400">Operação + administrativo</p>
            </div>
            <div class="p-4">
                <dt class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Resultado projetado</dt>
                <dd class="mt-1 font-display text-2xl font-bold text-slate-900 tabular">
                    +<span class="unit">R$</span> {{ Format::moneyDecimal(25531.43) }}
                </dd>
                <p class="mt-1 text-xs text-slate-400">Com previstos até dia 31</p>
            </div>
        </dl>
    </section>

    <section class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_480px]">
        {{-- Comparativo da frota: o pior primeiro. É o momento "aha". --}}
        <div class="min-w-0 rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5">
                <div>
                    <h2 class="font-display text-lg font-semibold text-slate-900">Comparativo da frota</h2>
                    <p class="text-xs text-slate-400">Ordenado por resultado · o pior primeiro</p>
                </div>
                <x-ui.button variant="ghost" size="sm">Ver DRE</x-ui.button>
            </div>

            <div class="max-h-[calc(100vh-22rem)] overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50">
                        <tr class="border-b border-slate-200">
                            <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Veículo</th>
                            <th class="hidden px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500 lg:table-cell">
                                Conjunto</th>
                            <th class="w-24 px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                km</th>
                            <th class="w-20 px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                km/l</th>
                            <th class="w-64 px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                R$/km</th>
                            <th class="w-32 px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-3"><x-ui.plate-chip plate="BRA2E19" type="tractor" /></td>
                            <td class="hidden px-3 text-slate-500 lg:table-cell">+ carreta</td>
                            <td class="px-3 text-right font-mono text-slate-600 tabular">{{ Format::moneyDecimal(8120, 0) }}</td>
                            <td class="px-3 text-right font-mono text-slate-600 tabular">{{ Format::moneyDecimal(2.11) }}</td>
                            <td class="px-3 py-1.5"><x-ui.km-gauge :revenue="3.12" :cost="3.44" :breakeven="3.30" compact /></td>
                            <td class="px-3 text-right font-mono font-medium text-danger-700 tabular">
                                −<span class="unit">R$</span> {{ Format::moneyDecimal(2596.00) }}
                            </td>
                        </tr>
                        <tr class="h-9 cursor-pointer border-b border-slate-100 bg-brand-50 shadow-[inset_2px_0_0_0_var(--color-brand-700)]">
                            <td class="px-3"><x-ui.plate-chip plate="RIO2A18" type="tractor" /></td>
                            <td class="hidden px-3 text-slate-500 lg:table-cell">+ 2 carretas</td>
                            <td class="px-3 text-right font-mono text-slate-600 tabular">{{ Format::moneyDecimal(10243, 0) }}</td>
                            <td class="px-3 text-right font-mono text-slate-600 tabular">{{ Format::moneyDecimal(2.43) }}</td>
                            <td class="px-3 py-1.5"><x-ui.km-gauge :revenue="4.37" :cost="3.95" :breakeven="3.78" compact /></td>
                            <td class="px-3 text-right font-mono font-medium text-slate-900 tabular">
                                +<span class="unit">R$</span> {{ Format::moneyDecimal(2120.00) }}
                            </td>
                        </tr>
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-3"><x-ui.plate-chip plate="MGA4F21" type="tractor" /></td>
                            <td class="hidden px-3 text-slate-500 lg:table-cell">solo</td>
                            <td class="px-3 text-right font-mono text-slate-600 tabular">{{ Format::moneyDecimal(6890, 0) }}</td>
                            <td class="px-3 text-right font-mono text-slate-600 tabular">{{ Format::moneyDecimal(3.02) }}</td>
                            <td class="px-3 py-1.5"><x-ui.km-gauge :revenue="4.90" :cost="3.80" :breakeven="3.60" compact /></td>
                            <td class="px-3 text-right font-mono font-medium text-slate-900 tabular">
                                +<span class="unit">R$</span> {{ Format::moneyDecimal(7580.00) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="sticky bottom-0 bg-slate-50">
                        <tr class="h-9 border-t border-slate-300">
                            <td class="px-3 text-2xs uppercase tracking-[0.12em] text-slate-500" colspan="2">Média da frota</td>
                            <td class="px-3 text-right font-mono text-slate-700 tabular">{{ Format::moneyDecimal(25253, 0) }}</td>
                            <td class="px-3 text-right font-mono text-slate-700 tabular">{{ Format::moneyDecimal(2.52) }}</td>
                            <td class="px-3 text-right font-mono text-slate-500 tabular">R$/km {{ Format::moneyDecimal(4.13) }}</td>
                            <td class="px-3 text-right font-mono font-medium text-slate-900 tabular">
                                +<span class="unit">R$</span> {{ Format::moneyDecimal(7104.00) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Master-detail: o veículo selecionado. A régua é o acento da tela. --}}
        <div class="rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5">
                <div class="flex items-center gap-2">
                    <x-ui.plate-chip plate="RIO2A18" type="tractor" />
                    <div>
                        <p class="font-display text-sm font-semibold text-slate-900">Scania R450</p>
                        <p class="text-xs text-slate-400">+ 2 carretas · julho/2026</p>
                    </div>
                </div>
                <x-ui.button variant="ghost" size="sm">Abrir DRE</x-ui.button>
            </div>

            <div class="p-4">
                <x-ui.km-gauge :revenue="4.37" :cost="3.95" :breakeven="3.78" />

                <dl class="mt-4 space-y-0 text-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 py-2">
                        <dt class="text-slate-500">Km rodados</dt>
                        <dd class="font-mono text-slate-900 tabular">{{ Format::moneyDecimal(10243, 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 py-2">
                        <dt class="text-slate-500">Receita líquida</dt>
                        <dd class="font-mono text-slate-900 tabular"><span class="unit">R$</span> {{ Format::moneyDecimal(43708.00) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 py-2">
                        <dt class="text-slate-500">Custos variáveis</dt>
                        <dd class="font-mono text-slate-900 tabular">−<span class="unit">R$</span> {{ Format::moneyDecimal(25250.00) }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <dt class="font-medium text-slate-700">Resultado líquido</dt>
                        <dd class="font-display text-lg font-semibold text-success-700 tabular">
                            +<span class="unit">R$</span> {{ Format::moneyDecimal(2120.00) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
@endsection
