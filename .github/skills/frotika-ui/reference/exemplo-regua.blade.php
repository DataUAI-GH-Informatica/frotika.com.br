{{--
  resources/views/components/ui/km-gauge.blade.php

  O ELEMENTO DE ASSINATURA DO FROTIKA.

  Custo por km é o número que decide a vida da transportadora. Ele merece ser
  um instrumento, não uma linha de tabela. Três marcas numa régua: receita/km,
  custo/km e o ponto de equilíbrio. A faixa entre receita e custo é a margem.

  Aparece em: cabeçalho do DRE · card de veículo · comparativo da frota · home mobile.
  Uma linguagem visual para o único número que importa.

  Toda a ousadia do sistema mora aqui. Tudo em volta é silêncio.

  Uso:
    <x-ui.km-gauge :revenue="4.37" :cost="3.95" :breakeven="3.78" />
    <x-ui.km-gauge :revenue="2.10" :cost="2.84" :breakeven="2.60" compact />
--}}

@props([
    'revenue',            // R$/km de receita líquida
    'cost',               // R$/km de custo total
    'breakeven' => null,  // R$/km de equilíbrio
    'compact' => false,   // versão de card / linha de tabela
])

@php
    $margin  = $revenue - $cost;
    $healthy = $margin >= 0;

    // Escala arredondada pra cima, com folga de 15%. Nunca escala automática
    // apertada: a régua tem que ser comparável entre veículos.
    $max = max(0.01, ceil(max($revenue, $cost, $breakeven ?? 0) * 1.15 * 2) / 2);
    $pct = fn (float $v): float => min(100, max(0, $v / $max * 100));
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }} role="img"
     aria-label="Receita {{ Format::moneyDecimal($revenue) }} por km, custo {{ Format::moneyDecimal($cost) }} por km, margem {{ $healthy ? 'positiva' : 'negativa' }} de {{ Format::moneyDecimal(abs($margin)) }} por km">

    @unless ($compact)
        <div class="mb-2 flex items-baseline justify-between">
            <span class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Resultado por km</span>
            <span @class([
                'font-mono tabular text-sm font-medium',
                'text-success-700' => $healthy,
                'text-danger-700'  => ! $healthy,
            ])>
                {{ $healthy ? '+' : '−' }}<span class="unit">R$</span> {{ Format::moneyDecimal(abs($margin)) }}<span class="unit">/km</span>
            </span>
        </div>
    @endunless

    <div class="relative">
        {{-- Trilho --}}
        <div class="space-y-1">
            {{-- Custo — sempre embaixo visualmente, é o que come a receita --}}
            <div class="flex items-center gap-2">
                @unless ($compact)
                    <span class="w-14 shrink-0 text-2xs text-slate-400">custo</span>
                @endunless
                <div class="h-2.5 flex-1 rounded-md bg-slate-100">
                    <div class="h-full rounded-md bg-slate-400" style="width: {{ $pct($cost) }}%"></div>
                </div>
                <span class="w-16 shrink-0 text-right font-mono tabular text-xs text-slate-600">{{ Format::moneyDecimal($cost) }}</span>
            </div>

            {{-- Receita --}}
            <div class="flex items-center gap-2">
                @unless ($compact)
                    <span class="w-14 shrink-0 text-2xs text-slate-400">receita</span>
                @endunless
                <div class="h-2.5 flex-1 rounded-md bg-slate-100">
                    <div @class([
                        'h-full rounded-md',
                        'bg-success-500' => $healthy,
                        'bg-danger-500'  => ! $healthy,
                    ]) style="width: {{ $pct($revenue) }}%"></div>
                </div>
                <span class="w-16 shrink-0 text-right font-mono tabular text-xs font-medium text-slate-900">{{ Format::moneyDecimal($revenue) }}</span>
            </div>
        </div>

        {{-- Marca do equilíbrio: filete vertical atravessando as duas barras --}}
        @if ($breakeven)
            <div class="pointer-events-none absolute inset-y-0 {{ $compact ? 'left-0 right-[4.5rem]' : 'left-16 right-[4.5rem]' }}">
                <div class="relative h-full">
                    <div class="absolute inset-y-0 w-px bg-slate-900" style="left: {{ $pct($breakeven) }}%"></div>
                </div>
            </div>
        @endif
    </div>

    @if ($breakeven && ! $compact)
        <div class="mt-1.5 text-2xs text-slate-400">
            Equilíbrio em <span class="font-mono tabular text-slate-600">R$ {{ Format::moneyDecimal($breakeven) }}/km</span>
        </div>
    @endif
</div>
