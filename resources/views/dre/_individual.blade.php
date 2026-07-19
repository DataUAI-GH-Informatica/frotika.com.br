@php
    use App\Domain\Fleet\Enums\VehicleType;
    use App\Domain\Finance\Enums\FinancialCategoryDreGroup;

    $m = $vehicle['metrics'];
    $g = $vehicle['groups_cents'];
    $km = $vehicle['km'];
    $net = $m['net_result_cents'];
    $netRevenue = $m['net_revenue_cents'];
    $perKm = $vehicle['per_km'];

    $typeLabel = $vehicle['type'] ? VehicleType::from($vehicle['type'])->label() : null;
    $netMarginPercent = $netRevenue !== 0 ? round(($net * 100) / $netRevenue, 1) : null;

    // %RL e R$/km de cada linha, sempre relativos à receita líquida e ao km.
    $fmtPct = fn (int $cents): string => $netRevenue !== 0 ? Format::percent(round(($cents * 100) / $netRevenue, 1)) : '—';
    $fmtPerKm = fn (int $cents): string => $km > 0 ? Format::moneyDecimal(($cents / 100) / $km) : '—';

    $catsByGroup = collect($vehicle['categories'])->groupBy('dre_group');

    $cellTone = fn (int $cents): string => $cents < 0 ? 'text-danger-700' : 'text-slate-900';
@endphp

<div class="space-y-4">
    {{-- Cabeçalho do veículo + régua (o elemento de assinatura) --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.plate-chip :plate="$vehicle['plate']" :type="$vehicle['type']" />
            <div class="min-w-0">
                <p class="font-display text-lg font-semibold text-slate-900">{{ Format::plate($vehicle['plate']) }}</p>
                @if ($typeLabel)
                    <p class="text-2xs uppercase tracking-wide text-slate-500">{{ $typeLabel }}</p>
                @endif
            </div>
            <div class="ml-auto text-right">
                <p class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Resultado líquido</p>
                <p @class([
                    'font-display text-2xl font-bold tabular',
                    'text-danger-700' => $net < 0,
                    'text-success-700' => $net >= 0,
                ])>{{ Format::money($net, true) }}</p>
            </div>
        </div>

        @if ($km > 0)
            <div class="mt-4 max-w-2xl">
                <x-ui.km-gauge :revenue="$perKm['revenue']" :cost="$perKm['cost']" :breakeven="$perKm['breakeven']" />
            </div>
        @else
            <p class="mt-3 text-sm text-slate-500">Sem km fechado no período (precisa de dois abastecimentos de tanque cheio) — a régua de R$/km fica indisponível.</p>
        @endif
    </div>

    {{-- Cards de indicadores --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <x-ui.stat-card label="Km rodados" :value="$km > 0 ? Format::km($km) : '—'" />
        <x-ui.stat-card label="Receita / km" :value="$km > 0 ? Format::moneyDecimal($perKm['revenue']) : '—'" unit="R$" />
        <x-ui.stat-card label="Custo / km" :value="$km > 0 ? Format::moneyDecimal($perKm['cost']) : '—'" unit="R$" />
        <x-ui.stat-card label="Margem líquida"
            :value="$netMarginPercent !== null ? Format::percent($netMarginPercent) : '—'"
            :tone="$net < 0 ? 'danger' : 'default'" />
        <x-ui.stat-card label="Consumo médio" :value="Format::consumption($vehicle['consumption'])" />
        <x-ui.stat-card label="Equilíbrio"
            :value="$vehicle['breakeven_km'] !== null ? Format::km($vehicle['breakeven_km']) : '—'" />
    </div>

    @if ($dre['apportionment']['method'] !== 'none')
        <p class="text-xs text-slate-500">
            {{ ['by_km' => 'Rateio por km rodado', 'by_revenue' => 'Rateio por receita', 'equal' => 'Rateio igual entre veículos'][$dre['apportionment']['method']] ?? 'Rateio' }}
            — este veículo representa
            <span class="font-mono tabular text-slate-700">{{ Format::percent($vehicle['apportionment']['basis_percent']) }}</span>
            da base da frota.
            @if ($dre['apportionment']['divisor_zero'])
                <span class="text-warning-700">Sem base de rateio no período; despesas rateadas ficaram zeradas.</span>
            @endif
        </p>
    @endif

    {{-- Waterfall: da receita líquida ao resultado --}}
    @include('dre._waterfall')

    {{-- Master-detail: DRE à esquerda, drill-down à direita --}}
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_480px]">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-2xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2 text-left">Linha</th>
                        <th class="w-32 px-3 py-2 text-right">Valor</th>
                        <th class="w-20 px-3 py-2 text-right">%RL</th>
                        <th class="w-24 px-3 py-2 text-right">R$/km</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- RECEITA BRUTA --}}
                    @include('dre._dre-subtotal', ['label' => 'RECEITA BRUTA', 'cents' => $g[FinancialCategoryDreGroup::GrossRevenue->value]])
                    @include('dre._dre-items', ['group' => FinancialCategoryDreGroup::GrossRevenue->value])

                    {{-- DEDUÇÕES --}}
                    @include('dre._dre-subtotal', ['label' => '(−) DEDUÇÕES', 'cents' => $g[FinancialCategoryDreGroup::Deductions->value]])
                    @include('dre._dre-items', ['group' => FinancialCategoryDreGroup::Deductions->value])

                    @include('dre._dre-total', ['label' => 'RECEITA LÍQUIDA', 'cents' => $m['net_revenue_cents']])

                    {{-- CUSTOS VARIÁVEIS --}}
                    @include('dre._dre-subtotal', ['label' => '(−) CUSTOS VARIÁVEIS', 'cents' => $g[FinancialCategoryDreGroup::VariableCost->value]])
                    @include('dre._dre-items', ['group' => FinancialCategoryDreGroup::VariableCost->value])

                    @include('dre._dre-total', ['label' => 'MARGEM DE CONTRIBUIÇÃO', 'cents' => $m['contribution_margin_cents']])

                    {{-- CUSTOS FIXOS --}}
                    @include('dre._dre-subtotal', ['label' => '(−) CUSTOS FIXOS DO VEÍCULO', 'cents' => $g[FinancialCategoryDreGroup::FixedCost->value]])
                    @include('dre._dre-items', ['group' => FinancialCategoryDreGroup::FixedCost->value])

                    @include('dre._dre-total', ['label' => 'RESULTADO OPERACIONAL', 'cents' => $m['operational_result_cents']])

                    {{-- Rateios (linha única cada) --}}
                    @include('dre._dre-apportioned', ['label' => '(−) RATEIO DE DESPESAS ADMINISTRATIVAS', 'cents' => $g[FinancialCategoryDreGroup::AdminExpense->value], 'key' => 'admin'])
                    @include('dre._dre-apportioned', ['label' => '(−) RATEIO DE DESPESAS FINANCEIRAS', 'cents' => $g[FinancialCategoryDreGroup::FinancialExpense->value], 'key' => 'financial'])

                    <tr class="border-t-2 border-slate-300 bg-slate-50">
                        <td class="px-3 py-2.5 font-display text-base font-bold text-slate-900">= RESULTADO LÍQUIDO DO VEÍCULO</td>
                        <td @class([
                            'px-3 py-2.5 text-right font-display text-base font-bold tabular',
                            'text-danger-700' => $net < 0,
                            'text-success-700' => $net >= 0,
                        ])>{{ Format::money($net, true) }}</td>
                        <td class="px-3 py-2.5 text-right font-mono tabular text-slate-500">{{ $fmtPct($net) }}</td>
                        <td class="px-3 py-2.5 text-right font-mono tabular text-slate-500">{{ $fmtPerKm($net) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Painel de drill-down --}}
        <div class="xl:sticky xl:top-20 xl:self-start">
            <div class="rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-3 py-2">
                    <h2 class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Detalhe da linha</h2>
                </div>

                <div id="detail-hint" class="px-4 py-10 text-center text-sm text-slate-500">
                    Clique numa linha do DRE para ver os lançamentos que a compõem.
                </div>

                @foreach ($vehicle['categories'] as $category)
                    @php $entries = $entriesByCategory[$category['category_id']] ?? []; @endphp
                    <div id="detail-cat-{{ $category['category_id'] }}" class="dre-detail hidden">
                        <div class="flex items-baseline justify-between gap-2 border-b border-slate-100 px-3 py-2">
                            <span class="text-sm font-medium text-slate-900">{{ $category['name'] }}</span>
                            <span class="font-mono tabular text-sm {{ $cellTone($category['amount_cents']) }}">{{ Format::money($category['amount_cents']) }}</span>
                        </div>
                        <div class="max-h-[28rem] overflow-auto">
                            @forelse ($entries as $entry)
                                <a href="{{ route('financial-entries.show', ['entry' => $entry['id']]) }}"
                                    class="flex items-center justify-between gap-2 border-b border-slate-50 px-3 py-1.5 text-xs hover:bg-slate-50">
                                    <span class="font-mono tabular text-slate-500">{{ Format::date($entry['competence_date']) }}</span>
                                    <span class="min-w-0 flex-1 truncate text-slate-700">{{ $entry['description'] ?: '—' }}</span>
                                    <span class="font-mono tabular {{ $cellTone($entry['amount_cents']) }}">{{ Format::money($entry['amount_cents']) }}</span>
                                </a>
                            @empty
                                <p class="px-3 py-4 text-xs text-slate-400">Sem lançamentos nesta categoria.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                <div id="detail-admin" class="dre-detail hidden px-4 py-6 text-sm text-slate-600">
                    Valor rateado das despesas administrativas da empresa, na proporção da base da frota
                    (<span class="font-mono tabular">{{ Format::percent($vehicle['apportionment']['basis_percent']) }}</span>).
                    Não há lançamentos diretos deste veículo aqui.
                </div>
                <div id="detail-financial" class="dre-detail hidden px-4 py-6 text-sm text-slate-600">
                    Valor rateado das despesas financeiras da empresa, na proporção da base da frota
                    (<span class="font-mono tabular">{{ Format::percent($vehicle['apportionment']['basis_percent']) }}</span>).
                    Não há lançamentos diretos deste veículo aqui.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function dreSelect(key) {
        document.querySelectorAll('.dre-detail').forEach((el) => el.classList.add('hidden'));
        document.querySelectorAll('[data-dre-line]').forEach((el) => {
            el.classList.remove('bg-brand-50');
        });

        const hint = document.getElementById('detail-hint');
        const panel = document.getElementById('detail-' + key);
        const line = document.getElementById('line-' + key);

        if (panel) {
            panel.classList.remove('hidden');
            if (hint) hint.classList.add('hidden');
        }

        if (line) {
            line.classList.add('bg-brand-50');
        }
    }
</script>
