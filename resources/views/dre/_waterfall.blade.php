@php
    $wfContribution = $m['contribution_margin_cents'];
    $wfOperational = $m['operational_result_cents'];
    $wfAfterAdmin = $m['operational_result_cents'] + $m['admin_expense_cents'];

    $columns = [
        ['label' => 'Rec. líq.', 'kind' => 'total', 'value' => $netRevenue],
        ['label' => 'Variáveis', 'kind' => 'delta', 'from' => $netRevenue, 'to' => $wfContribution],
        ['label' => 'Margem', 'kind' => 'total', 'value' => $wfContribution],
        ['label' => 'Fixos', 'kind' => 'delta', 'from' => $wfContribution, 'to' => $wfOperational],
        ['label' => 'Operac.', 'kind' => 'total', 'value' => $wfOperational],
        ['label' => 'Adm.', 'kind' => 'delta', 'from' => $wfOperational, 'to' => $wfAfterAdmin],
        ['label' => 'Financ.', 'kind' => 'delta', 'from' => $wfAfterAdmin, 'to' => $net],
        ['label' => 'Resultado', 'kind' => 'result', 'value' => $net],
    ];

    $domain = [0, $netRevenue, $wfContribution, $wfOperational, $wfAfterAdmin, $net];
    $maxV = max($domain);
    $minV = min($domain);
    $hasData = $maxV !== $minV;

    $n = count($columns);
    $barW = 44;
    $gap = 24;
    $padL = 12;
    $padR = 12;
    $top = 10;
    $plotH = 150;
    $bottom = $top + $plotH;
    $width = $padL + $n * $barW + ($n - 1) * $gap + $padR;
    $height = $bottom + 46;

    $span = $hasData ? $maxV - $minV : 1;
    $y = fn (int $cents): float => $top + (($maxV - $cents) / $span) * $plotH;
    $baseline = $y(0);
@endphp

@if ($hasData)
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <h2 class="mb-1 text-2xs font-semibold uppercase tracking-wide text-slate-500">Da receita líquida ao resultado</h2>
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-56 w-full" role="img"
            aria-label="Cascata de resultado do veículo">
            <line x1="{{ $padL }}" y1="{{ $baseline }}" x2="{{ $width - $padR }}" y2="{{ $baseline }}"
                stroke="currentColor" class="text-slate-200" stroke-width="1" />

            @foreach ($columns as $i => $col)
                @php
                    $x = $padL + $i * ($barW + $gap);

                    if ($col['kind'] === 'delta') {
                        $yFrom = $y($col['from']);
                        $yTo = $y($col['to']);
                        $rectY = min($yFrom, $yTo);
                        $rectH = max(1.0, abs($yTo - $yFrom));
                        $barValue = $col['to'] - $col['from'];
                        $fill = 'text-brand-800';
                    } else {
                        $value = $col['value'];
                        $yVal = $y($value);
                        $rectY = min($yVal, $baseline);
                        $rectH = max(1.0, abs($baseline - $yVal));
                        $barValue = $value;

                        if ($col['kind'] === 'result') {
                            $fill = $value < 0 ? 'text-danger-600' : 'text-success-600';
                        } else {
                            $fill = 'text-brand-500';
                        }
                    }

                    $labelY = $bottom + 16;
                @endphp

                <rect x="{{ $x }}" y="{{ round($rectY, 1) }}" width="{{ $barW }}" height="{{ round($rectH, 1) }}"
                    rx="2" fill="currentColor" class="{{ $fill }}" />

                <text x="{{ $x + $barW / 2 }}" y="{{ round($rectY, 1) - 4 }}" text-anchor="middle"
                    class="fill-slate-500" style="font-size: 9px; font-family: 'IBM Plex Mono', monospace;">{{ Format::moneyDecimal($barValue / 100, 0) }}</text>

                <text x="{{ $x + $barW / 2 }}" y="{{ $labelY }}" text-anchor="middle"
                    class="fill-slate-400" style="font-size: 9px;">{{ $col['label'] }}</text>
            @endforeach
        </svg>
    </div>
@endif
