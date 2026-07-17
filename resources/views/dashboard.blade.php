@extends('layouts.app')

@section('title', 'Painel | Frotika')

@section('content')
    <x-ui.page-header title="Painel operacional" subtitle="Julho de 2026 - consolidado da frota">
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm">Importar CT-e</x-ui.button>
            <x-ui.button size="sm">Nova viagem</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Saldo consolidado" value="R$ 128.420,55" tone="default" hint="Base caixa + bancos" />
        <x-ui.stat-card label="Receita do mes" value="R$ 214.973,10" tone="success" hint="Competencia atual" />
        <x-ui.stat-card label="Custos do mes" value="R$ 189.441,67" tone="danger" hint="Operacao + administrativo" />
        <x-ui.stat-card label="Resultado projetado" value="R$ 25.531,43" tone="info" hint="Com previstos ate dia 31" />
    </section>

    <section class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_480px]">
        <x-ui.card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
                <div>
                    <p class="font-display text-lg font-semibold text-slate-900">Ranking de veiculos</p>
                    <p class="text-xs text-slate-500">Ordenado por resultado liquido</p>
                </div>
                <x-ui.button variant="ghost" size="sm">Ver comparativo</x-ui.button>
            </div>

            <div class="max-h-[calc(100vh-22rem)] overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50">
                        <tr class="h-9">
                            <th class="px-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Placa</th>
                            <th
                                class="w-28 px-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                R$/km</th>
                            <th
                                class="w-28 px-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Custo/km</th>
                            <th
                                class="w-28 px-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                km/l</th>
                            <th
                                class="w-32 px-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            class="h-9 cursor-pointer border-b border-slate-100 bg-brand-50 shadow-[inset_2px_0_0_0_var(--color-brand-700)]">
                            <td class="px-3"><x-ui.plate-chip plate="RIO2A18" type="tractor" /></td>
                            <td class="px-3 text-right font-mono tabular text-slate-900">4,37</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">3,95</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">2,43</td>
                            <td class="px-3 text-right font-mono tabular text-success-700"><span class="unit">R$</span>
                                2.120,00</td>
                        </tr>
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-3"><x-ui.plate-chip plate="MGA4F21" type="tractor" /></td>
                            <td class="px-3 text-right font-mono tabular text-slate-900">4,90</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">3,80</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">3,02</td>
                            <td class="px-3 text-right font-mono tabular text-success-700"><span class="unit">R$</span>
                                7.580,00</td>
                        </tr>
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-3"><x-ui.plate-chip plate="BRA2E19" type="semi_trailer" /></td>
                            <td class="px-3 text-right font-mono tabular text-slate-900">3,12</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">3,44</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">2,11</td>
                            <td class="px-3 text-right font-mono tabular text-danger-700">-<span class="unit">R$</span>
                                2.596,00</td>
                        </tr>
                    </tbody>
                    <tfoot class="sticky bottom-0 border-t border-slate-300 bg-slate-50">
                        <tr class="h-9">
                            <td class="px-3 text-xs uppercase tracking-[0.12em] text-slate-500">Media da frota</td>
                            <td class="px-3 text-right font-mono tabular text-slate-900">4,13</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">3,73</td>
                            <td class="px-3 text-right font-mono tabular text-slate-700">2,52</td>
                            <td class="px-3 text-right font-mono tabular text-slate-900"><span class="unit">R$</span>
                                2.368,00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card>
            <p class="font-display text-lg font-semibold text-slate-900">Detalhe do veiculo selecionado</p>
            <p class="mt-1 text-sm text-slate-500">RIO2A18 - Scania R450</p>

            <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Regua de R$/km</p>
                <div class="mt-3 space-y-2">
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

                <p class="mt-3 text-sm text-slate-700">
                    Margem por km: <span class="font-mono tabular text-success-700">+0,42</span>
                </p>
            </div>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Km rodados</dt>
                    <dd class="font-mono tabular text-slate-900">10.243</dd>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Receita liquida</dt>
                    <dd class="font-mono tabular text-slate-900"><span class="unit">R$</span> 43.708,00</dd>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Custos variaveis</dt>
                    <dd class="font-mono tabular text-slate-900">-<span class="unit">R$</span> 25.250,00</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="font-medium text-slate-700">Resultado liquido</dt>
                    <dd class="font-mono tabular font-semibold text-success-700"><span class="unit">R$</span> 2.120,00
                    </dd>
                </div>
            </dl>
        </x-ui.card>
    </section>
@endsection
