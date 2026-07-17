# Anatomia dos componentes — Frotika

Átomos do sistema. Classes exatas. Se você está inventando markup, pare e copie daqui.
Tudo vive em `resources/views/components/ui/`.

> Padrões de tela (tabela completa, master-detail, teclado, densidade) estão em [desktop.md](./desktop.md) — o uso principal do sistema.

## Botão

| Variante | Classes |
| --- | --- |
| primary | `h-9 px-3 rounded-md bg-brand-700 text-white text-sm font-medium hover:bg-brand-600 active:bg-brand-800` |
| secondary | `h-9 px-3 rounded-md border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50` |
| ghost | `h-9 px-2 rounded-md text-slate-600 text-sm font-medium hover:bg-slate-100` |
| danger | `h-9 px-3 rounded-md bg-danger-500 text-white text-sm font-medium hover:bg-danger-700` |

Mobile: `h-12 w-full` e a barra de ação fica fixa no rodapé. Nunca sombra. Nunca gradiente.

## Superfície

```html
<!-- Card. Border + degrau de fundo. NÃO tem sombra. -->
<div class="rounded-lg border border-slate-200 bg-white">
  <div class="border-b border-slate-200 px-4 py-3">
    <h2 class="font-display text-lg font-semibold text-slate-900">Título</h2>
  </div>
  <div class="p-4">…</div>
</div>
```

Overlay (modal, popover, dropdown, sheet) — **único lugar com sombra**:
`rounded-lg border border-slate-200 bg-white shadow-overlay`

## Tabela

```html
<table class="w-full text-sm">
  <thead>
    <tr class="border-b border-slate-200 bg-slate-50">
      <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Veículo</th>
      <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Litros</th>
      <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Valor</th>
    </tr>
  </thead>
  <tbody>
    <tr class="h-row border-b border-slate-100 hover:bg-slate-50">
      <td class="px-3 py-2"><x-ui.plate-chip plate="RIO2A18" type="tractor" /></td>
      <td class="px-3 py-2 text-right font-mono tabular text-slate-900">245,300 <span class="unit">L</span></td>
      <td class="px-3 py-2 text-right font-mono tabular text-slate-900"><span class="unit">R$</span> 1.372,00</td>
    </tr>
  </tbody>
</table>
```

- Linha **36px no desktop** (`h-9`), 44px em touch (`h-11`). 44 é alvo de toque, não medida de tela.
- Cabeçalho **sticky**, `bg-slate-50`, 11px, uppercase, tracking.
- Separador `border-slate-100` entre linhas, `border-slate-200` no cabeçalho.
- **Sem zebra.** O filete já separa; zebra ruidosa em tabela densa.
- Célula numérica: `text-right font-mono tabular`.
- `rounded-none` em célula. Sempre.
- Ordenação por clique, largura fixa em coluna numérica, totais sticky, seleção em lote: **[desktop.md](./desktop.md)**. O trecho acima é só o esqueleto.
- Abaixo de 768px a tabela não existe — vira card. Ver [mobile.md](./mobile.md).

## Valor monetário

```html
<span class="font-mono tabular text-slate-900"><span class="unit">R$</span> 1.372,00</span>
```

Negativo: `text-danger-700` **e sinal explícito** (`−R$ 1.372,00`). Cor nunca é o único portador de significado.

## Card de indicador

```html
<div class="rounded-lg border border-slate-200 bg-white p-4">
  <div class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Custo por km</div>
  <div class="mt-1 font-display text-2xl font-bold tabular text-slate-900">
    <span class="unit">R$</span> 3,95
  </div>
  <div class="mt-1 text-xs text-slate-400">últimos 30 dias · 10.243 km</div>
</div>
```

Sem ícone. Sem sombra. Sem cor de fundo. O número é o herói.

## Chip de placa

Padrão Mercosul. Tarja superior `brand-900` cheia no trator, tracejada no reboque —
diferença de 2px que informa. Todo veículo aparece assim, nunca como texto puro.

```html
<span class="inline-flex select-none flex-col overflow-hidden rounded-md border border-slate-800 bg-white align-middle">
  <span @class(['h-[3px] w-full bg-brand-900', 'opacity-40' => $type === 'semi_trailer'])></span>
  <span class="px-1.5 py-0.5 font-mono text-xs font-medium uppercase tracking-widest text-slate-900">{{ $plate }}</span>
</span>
```

## Cabeçalho de página

```html
<div class="mb-6 flex items-start justify-between gap-4">
  <div>
    <h1 class="font-display text-xl font-bold text-slate-900">Abastecimentos</h1>
    <p class="mt-0.5 text-sm text-slate-500">142 lançamentos · março de 2026</p>
  </div>
  <div class="hidden items-center gap-2 lg:flex">{{ $actions }}</div>
</div>
```

Ações some em mobile — viram FAB ou barra fixa no rodapé.

## Badge

```html
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
             bg-success-50 text-success-700">Autorizado</span>
```

`rounded-full` é permitido **só aqui e em avatar**. Só cor semântica com significado.

## Estado vazio

```html
<div class="border-t border-slate-200 px-4 py-12 text-center">
  <p class="font-display text-lg font-semibold text-slate-900">Nenhuma viagem ainda.</p>
  <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
    Importe os XMLs dos seus CT-es e as viagens aparecem aqui, com a receita já preenchida.
  </p>
  <div class="mt-4 flex justify-center gap-2">
    <x-ui.button variant="primary" icon="upload">Importar CT-e</x-ui.button>
    <x-ui.button variant="secondary">Criar viagem manualmente</x-ui.button>
  </div>
</div>
```

Sem ilustração. Sem ícone gigante em círculo cinza.

## Sidebar

- Fundo: gradiente vertical `brand-950` → `brand-900`. **Único gradiente do sistema.**
- Texto `brand-100`. Item ativo: `bg-brand-800`, barra de 3px `accent-500` à esquerda, texto branco.
- Hover `bg-brand-800/60`.
- Rótulo de seção: 11px, `brand-400`, uppercase, tracking largo. Some ao recolher; o filete fica.
- Recolhida: só ícone, tooltip à direita após 400ms. **Submenu vira flyout**, não expansão.
- Estado em `users.preferences.sidebar_collapsed`, aplicado no servidor no primeiro render. **A classe sai da Blade, não do JS** — senão pisca.
- Transição de largura 200ms `ease-out`. Atalho `[`.

## Topbar

64px, `bg-white`, `border-b border-slate-200`, sticky.

Esquerda: hambúrguer (mobile) · seletor de empresa (só renderiza se o grupo tem >1).
Centro: busca global `⌘K`.
Direita: atalhos · sino · avatar.

Atalhos (a "sidebar superior"): até 4, escolhidos pelo usuário, em `users.preferences.shortcuts`.
Abrem **slide-over**, não navegam — a pessoa lança e volta pro que estava fazendo.
Abaixo de 1280px: só ícone. Respeitam a permissão do papel.
