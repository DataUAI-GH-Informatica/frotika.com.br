{{--
  PÁGINA DE REFERÊNCIA — resources/views/livewire/fuelings/index.blade.php

  ============================================================================
  TODA LISTAGEM DO FROTIKA COPIA ESTA ESTRUTURA. Não invente listagem.
  ============================================================================

  O uso principal do Frotika é DESKTOP. A tabela é o produto: 80% do tempo de
  tela é aqui. Por isso ela tem, obrigatoriamente:

    · cabeçalho sticky           · ordenação por clique, refletida na URL
    · densidade 36px             · seleção em lote com barra no RODAPÉ
    · largura fixa nos números   · teclado (↑↓ Enter Esc Space / n)
    · master-detail — a lista NÃO some ao abrir o registro
    · totais sticky              · skeleton com a geometria da tabela
    · filtros inline e live      · configuração de coluna

  Mobile (<768px) vira card de 3 linhas. Ver reference/mobile.md.

  Observe o que NÃO tem: sombra em card, rounded-xl, gradiente, zebra, ícone
  decorativo, paginação de 25 em 25, modal de formulário, cor sem função.
--}}

<div x-data="tableKeys()" @keydown.window="handle($event)">

    {{-- ── Cabeçalho ───────────────────────────────────────────────────── --}}
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-bold text-slate-900">Abastecimentos</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $fuelings->total() }} lançamentos · {{ $periodLabel }}</p>
        </div>

        {{-- Some no mobile: a ação vira FAB na zona do polegar --}}
        <div class="hidden items-center gap-2 lg:flex">
            <x-ui.density-toggle wire:model.live="density" />
            <x-ui.column-config table="fuelings" :columns="$this->availableColumns" wire:model.live="visibleColumns" />
            <x-ui.button variant="secondary" icon="download" wire:click="export">Exportar</x-ui.button>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-fueling-form')">
                Novo abastecimento <x-ui.kbd>n</x-ui.kbd>
            </x-ui.button>
        </div>
    </div>

    {{-- ── Filtros: inline, live, na URL. Nunca modal, nunca "Aplicar". ── --}}
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <x-ui.date-range-input wire:model.live="period" class="w-full sm:w-auto" />
        <x-ui.select wire:model.live="vehicleId" placeholder="Todos os veículos" class="w-full sm:w-52">
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}">{{ $vehicle->plate }} · {{ $vehicle->model }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="driverId" placeholder="Todos os motoristas" class="w-full sm:w-52" />

        {{-- Filtro ativo vira chip removível --}}
        @foreach ($this->activeFilterChips as $chip)
            <button type="button" wire:click="removeFilter('{{ $chip['key'] }}')"
                    class="inline-flex h-7 items-center gap-1 rounded-md border border-slate-300 bg-white px-2 text-xs text-slate-600 hover:bg-slate-50">
                {{ $chip['label'] }} <x-icon name="x" class="size-3 text-slate-400" />
            </button>
        @endforeach

        {{-- "/" foca aqui --}}
        <x-ui.search-input wire:model.live.debounce.300ms="search" x-ref="search"
                           placeholder="Buscar posto, nota…" class="ms-auto w-full sm:w-64" />
    </div>

    <div class="flex gap-4">

        {{-- ══ LISTA — encolhe quando o painel abre, NÃO some ═══════════ --}}
        <div class="min-w-0 flex-1">
            <div class="relative rounded-lg border border-slate-200 bg-white">

                {{-- ── DESKTOP: a tabela ──────────────────────────────── --}}
                <div class="hidden max-h-[calc(100vh-15rem)] overflow-auto md:block">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-slate-200 bg-slate-50">
                                <th class="w-9 px-3">
                                    <x-ui.checkbox wire:model.live="selectAll" aria-label="Selecionar todos" />
                                </th>
                                <x-ui.th sort="fueled_at"    :current="$sort" class="w-24">Data</x-ui.th>
                                <x-ui.th sort="vehicle"      :current="$sort" class="w-28">Veículo</x-ui.th>
                                <x-ui.th sort="station"      :current="$sort">Posto</x-ui.th>
                                {{-- Numéricas com largura FIXA: sem isso a coluna dança ao filtrar --}}
                                <x-ui.th sort="odometer"     :current="$sort" numeric class="w-28">Odômetro</x-ui.th>
                                <x-ui.th sort="liters"       :current="$sort" numeric class="w-28">Litros</x-ui.th>
                                <x-ui.th sort="km_per_liter" :current="$sort" numeric class="w-24">km/l</x-ui.th>
                                <x-ui.th sort="total_cents"  :current="$sort" numeric class="w-32">Valor</x-ui.th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>

                        <tbody wire:loading.class="opacity-40">
                            @forelse ($fuelings as $i => $fueling)
                                <tr wire:key="f-{{ $fueling->id }}"
                                    x-ref="row-{{ $i }}"
                                    @click="select({{ $i }}, {{ $fueling->id }})"
                                    @class([
                                        'cursor-pointer border-b border-slate-100 hover:bg-slate-50',
                                        'h-9'  => $density === 'compact',   // 36px — padrão desktop
                                        'h-11' => $density === 'normal',    // 44px — touch
                                        // selecionada: barra de 2px à esquerda, não só fundo
                                        'bg-brand-50 shadow-[inset_2px_0_0_0_var(--color-brand-700)]' => $selectedId === $fueling->id,
                                    ])
                                    :class="focused === {{ $i }} && 'ring-2 ring-inset ring-brand-500'">

                                    <td class="px-3" @click.stop>
                                        <x-ui.checkbox wire:model.live="selected" value="{{ $fueling->id }}"
                                                       @click="range($event, {{ $i }})" />
                                    </td>
                                    <td class="px-3 font-mono tabular text-slate-600">{{ Format::date($fueling->fueled_at) }}</td>
                                    <td class="px-3"><x-ui.plate-chip :plate="$fueling->vehicle->plate" :type="$fueling->vehicle->type" /></td>
                                    <td class="truncate px-3 text-slate-600">{{ $fueling->station_name }}</td>
                                    <td class="px-3 text-right font-mono tabular text-slate-600">{{ Format::km($fueling->odometer) }}</td>
                                    <td class="px-3 text-right font-mono tabular text-slate-900">{{ Format::liters($fueling->liters) }}</td>

                                    {{-- Consumo é null sem tanque cheio anterior. NÃO é zero. --}}
                                    <td class="px-3 text-right font-mono tabular">
                                        @if ($fueling->km_per_liter)
                                            <span @class(['text-slate-900', 'text-warning-700' => $fueling->isConsumptionOutlier()])>
                                                {{ Format::consumption($fueling->km_per_liter) }}
                                            </span>
                                        @else
                                            <span class="text-slate-300" title="Sem tanque cheio anterior para fechar o intervalo">—</span>
                                        @endif
                                    </td>

                                    <td class="px-3 text-right font-mono tabular text-slate-900">
                                        <span class="unit">R$</span> {{ Format::moneyDecimal($fueling->total_cents / 100) }}
                                    </td>
                                    <td class="px-3" @click.stop><x-ui.row-menu :model="$fueling" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="9">
                                    <x-ui.empty-state :filtered="$this->hasFilters">
                                        <x-slot:title>Nenhum abastecimento ainda.</x-slot:title>
                                        Lance o primeiro e o consumo do veículo começa a aparecer aqui.
                                        <x-slot:actions>
                                            <x-ui.button variant="primary" wire:click="$dispatch('open-fueling-form')">Novo abastecimento</x-ui.button>
                                        </x-slot:actions>
                                    </x-ui.empty-state>
                                </td></tr>
                            @endforelse
                        </tbody>

                        {{-- Totais sticky: o número que a pessoa veio conferir --}}
                        @if ($fuelings->isNotEmpty())
                            <tfoot class="sticky bottom-0">
                                <tr class="border-t border-slate-300 bg-slate-50 font-medium">
                                    <td colspan="5" class="px-3 py-2 text-2xs uppercase tracking-wide text-slate-500">Total do período</td>
                                    <td class="px-3 py-2 text-right font-mono tabular text-slate-900">{{ Format::liters($this->totals['liters']) }}</td>
                                    <td class="px-3 py-2 text-right font-mono tabular text-slate-600">{{ Format::consumption($this->totals['avg_kml']) }}</td>
                                    <td class="px-3 py-2 text-right font-mono tabular text-slate-900">
                                        <span class="unit">R$</span> {{ Format::moneyDecimal($this->totals['amount'] / 100) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- Skeleton mantém a geometria da tabela. Nunca spinner. --}}
                <div wire:loading.delay.longer wire:target="period,vehicleId,driverId,search,sort"
                     class="absolute inset-0 hidden bg-white md:block">
                    <x-ui.table-skeleton :rows="14" :columns="9" />
                </div>

                {{-- ── MOBILE: cards de 3 linhas. Tabela NÃO rola de lado. ── --}}
                <div class="divide-y divide-slate-100 md:hidden">
                    @foreach ($fuelings as $fueling)
                        <button wire:key="fm-{{ $fueling->id }}" type="button"
                                wire:click="$dispatch('open-fueling-form', { id: {{ $fueling->id }} })"
                                class="block w-full px-4 py-3 text-left active:bg-slate-50"
                                style="touch-action: manipulation">
                            <div class="flex items-center justify-between gap-3">
                                <x-ui.plate-chip :plate="$fueling->vehicle->plate" :type="$fueling->vehicle->type" />
                                <span class="font-mono tabular text-sm font-medium text-slate-900">
                                    <span class="unit">R$</span> {{ Format::moneyDecimal($fueling->total_cents / 100) }}
                                </span>
                            </div>
                            <div class="mt-1 font-mono tabular text-sm text-slate-600">
                                {{ Format::liters($fueling->liters) }}
                                @if ($fueling->km_per_liter) · {{ Format::consumption($fueling->km_per_liter) }} @endif
                            </div>
                            <div class="mt-0.5 truncate text-xs text-slate-400">
                                {{ Format::date($fueling->fueled_at) }} · {{ $fueling->station_name }}
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Sem paginação abaixo de 500 registros: role. --}}
                @if ($fuelings->hasPages())
                    <div class="border-t border-slate-200 px-3 py-2">{{ $fuelings->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ══ MASTER-DETAIL: 480px. A lista encolhe, não some. ═════════
             Auditar 20 lançamentos não pode custar 40 navegações. --}}
        @if ($selectedId)
            <div class="hidden w-[480px] shrink-0 xl:block">
                <div class="sticky top-20 rounded-lg border border-slate-200 bg-white">
                    <livewire:fuelings.detail :fuelingId="$selectedId" :key="'d-'.$selectedId" />
                </div>
            </div>
        @endif
    </div>

    {{-- ── Barra de lote: RODAPÉ. No topo empurraria a tabela. ─────────── --}}
    @if (count($selected))
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white px-6 py-3 shadow-overlay">
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600">
                    <strong class="font-mono tabular">{{ count($selected) }}</strong> selecionados
                </span>
                <button wire:click="$set('selected', [])" class="text-sm text-slate-400 hover:text-slate-600">Limpar</button>
                <div class="ms-auto flex gap-2">
                    <x-ui.button variant="secondary" wire:click="bulkExport">Exportar</x-ui.button>
                    <x-ui.button variant="danger" wire:click="confirmBulkDelete">Excluir</x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Mobile: ação primária na zona do polegar. Nunca no topo. ────── --}}
    <button type="button" wire:click="$dispatch('open-fueling-form')"
            class="fixed bottom-20 right-4 z-20 flex size-14 items-center justify-center rounded-full bg-brand-700 text-white shadow-overlay active:bg-brand-800 md:hidden"
            aria-label="Novo abastecimento">
        <x-icon name="plus" class="size-6" />
    </button>
</div>

@script
<script>
// Teclado: quem lança 40 abastecimentos não usa o mouse.
// ↑↓ navega COM o painel acompanhando — é assim que se audita.
Alpine.data('tableKeys', () => ({
    focused: null,

    select(i, id) { this.focused = i; $wire.set('selectedId', id); },

    range(e, i) { if (e.shiftKey) $wire.selectRange(i); },

    handle(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
            if (e.key === 'Escape') e.target.blur();
            return;
        }
        const rows = $wire.rowIds ?? [];
        switch (e.key) {
            case 'ArrowDown': e.preventDefault(); this.move(1, rows);  break;
            case 'ArrowUp':   e.preventDefault(); this.move(-1, rows); break;
            case 'Enter':     if (this.focused !== null) $wire.set('selectedId', rows[this.focused]); break;
            case 'Escape':    $wire.set('selectedId', null); this.focused = null; break;
            case ' ':         if (this.focused !== null) { e.preventDefault(); $wire.toggle(rows[this.focused]); } break;
            case '/':         e.preventDefault(); this.$refs.search?.focus(); break;
            case 'n':         $wire.dispatch('open-fueling-form'); break;
        }
    },

    move(delta, rows) {
        this.focused = Math.max(0, Math.min(rows.length - 1, (this.focused ?? -1) + delta));
        this.$refs[`row-${this.focused}`]?.scrollIntoView({ block: 'nearest' });
        $wire.set('selectedId', rows[this.focused]); // o painel acompanha
    },
}));
</script>
@endscript
