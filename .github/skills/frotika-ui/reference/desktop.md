# Desktop no Frotika

**Aqui é onde o produto acontece.** Mobile é lançamento rápido; desktop é o trabalho.

Quem fica no desktop é a pessoa do escritório — muitas vezes o próprio dono — que abre o sistema às 7h e fecha às 18h. Ela não "visita" a tela: ela mora nela. Concilia 40 abastecimentos, importa 30 CT-es, audita o DRE de 6 veículos. Otimizar para o primeiro clique dela é errar o alvo. **Otimize para o milésimo.**

Isso muda tudo:

- Densidade não é preferência, é ferramenta. Cada linha a mais na tela é uma rolagem a menos, mil vezes por dia.
- Teclado não é acessibilidade, é velocidade. Quem repete uma tarefa 40 vezes não usa o mouse.
- Contexto não pode se perder. Auditar 20 lançamentos abrindo e fechando 20 páginas é o que faz a pessoa voltar pro Excel.

## Densidade

| Modo | Linha | Onde |
| --- | --- | --- |
| **Compacto** | **36px** | **padrão no desktop** |
| Normal | 44px | padrão em touch e tablet |

**Correção importante:** a regra dos 44px é alvo de toque. No desktop ela não se aplica — é só ar desperdiçado. O padrão do desktop é 36px. O toggle fica no cabeçalho da tabela e persiste em `users.preferences.density`.

**Meta dura:** em 1440×900, no modo compacto, uma listagem mostra **≥16 linhas sem rolar**. Conta: 900 − 64 (topbar) − 72 (cabeçalho) − 48 (filtros) − 32 (thead) − 40 (rodapé) − 48 (padding) ≈ 596px ÷ 36 ≈ 16. Se der menos, algo está gordo. Meça, não estime.

## Shell e grade

```
1440 × 900
┌────────────┬──────────────────────────────────────────────────────────────┐
│            │ topbar 64                                                    │
│  sidebar   ├──────────────────────────────────────────────────────────────┤
│    264     │  Frotika › Abastecimentos                            [24px]  │
│            │  ┌────────────────────────────────────────────────────────┐  │
│            │  │ Abastecimentos            [densidade] [⚙] [Exportar] [+]│  │
│            │  ├────────────────────────────────────────────────────────┤  │
│            │  │ [período] [veículo ▾] [motorista ▾]  · 3 filtros ativos│  │
│            │  ├────────────────────────────────────────────────────────┤  │
│            │  │ ▓▓▓▓▓ thead sticky ▓▓▓▓▓                               │  │
│            │  │ ─── 16 linhas × 36px ─────────────────────────────────  │  │
│            │  │ ▓▓▓▓▓ totais sticky ▓▓▓▓▓                              │  │
│            │  └────────────────────────────────────────────────────────┘  │
└────────────┴──────────────────────────────────────────────────────────────┘
```

### Largura

**Não capar tudo em 1400.** Quem comprou um monitor de 27" comprou para ver mais colunas. Capar a tabela nele é desperdiçar o dinheiro do usuário.

| Região | Largura |
| --- | --- |
| Tabela, matriz, comparativo | **fluida**, `px-6`, sem `max-w` |
| Formulário, configuração | `max-w-3xl` (768px) |
| Texto corrido, ajuda | `max-w-prose` (~72ch) |
| DRE | fluida até 1600, depois centraliza |

Motivo: linha de texto com 200 caracteres é ilegível; coluna numérica com 200 caracteres é útil. A regra segue o conteúdo, não a página.

### Breakpoints

`≥1024` sidebar expandida · `768–1023` sidebar recolhida (72px) · `<768` bottom nav, ver [mobile.md](./mobile.md).

## A tabela de dados

**É o produto.** 80% do tempo de tela é aqui. Se a tabela é medíocre, o sistema é medíocre — o resto não compensa.

Implementação de referência: [exemplo-lista.blade.php](./exemplo-lista.blade.php). **Toda listagem copia aquele arquivo.** Não invente.

### Obrigatório

- **Cabeçalho sticky** (`sticky top-0 z-10 bg-slate-50`). Rolar 200 linhas e não saber qual coluna é qual é falha, não detalhe.
- **Ordenação por clique** no cabeçalho. Seta de 12px. Ordenação persiste na URL (`?sort=-total`) — a pessoa manda o link pro contador.
- **Larguras fixas em coluna numérica** (`w-28`, `w-32`). Motivo: sem isso a coluna dança enquanto o filtro digita, e o olho perde a linha.
- **Sem paginação abaixo de 500 registros.** Role. Paginação de 25 em 25 num sistema de 140 abastecimentos/mês é fricção inventada.
- **Linha de totais sticky no rodapé** em toda tabela financeira. É o número que a pessoa veio conferir.
- **Skeleton com a geometria certa** durante o carregamento, não spinner. A tabela mantém a forma; só o conteúdo pisca.
- **Linha inteira clicável.** Sem coluna "Ver".
- **Sem zebra.** O filete de 1px já separa.

### Seleção e ação em lote

Sem isso, "criar viagem a partir de 12 CT-es" vira 12 operações.

- Coluna de checkbox à esquerda, 36px. `Shift+clique` seleciona intervalo.
- Select-all no cabeçalho seleciona **a página**; se houver mais, aparece "Selecionar todos os 143".
- **Barra de lote surge no rodapé**, fixa, não no topo. Motivo: no topo ela empurra a tabela e a pessoa perde a linha que estava olhando.
- A barra diz o que vai acontecer: `12 CT-es selecionados · [Criar viagem] [Vincular a viagem existente] [Exportar]`.

### Configuração de coluna

Ícone `⚙` no cabeçalho → popover com as colunas, arrastáveis, com toggle. Persiste em `users.preferences.columns.{tabela}`.

Motivo: a pessoa que concilia quer ver `document_number`; a que analisa quer ver `km_per_liter`. Mostrar as duas para as duas é ruído.

### Edição inline

Onde a tarefa é repetitiva, **editar não abre modal**.

Fluxo de caixa: clica no `paid_at`, edita, `Tab` vai pro próximo, `Esc` cancela, `Enter` salva e desce. Dar baixa em 30 lançamentos tem que ser 30 `Tab`, não 30 modais.

Célula editável: `hover:ring-1 ring-slate-300 cursor-text`. Foco: `ring-2 ring-brand-500`. Salvando: `opacity-60`. Erro: `ring-danger-500` + mensagem embaixo, sem toast.

## Master-detail: o padrão que define o sistema

**Auditar não pode custar o contexto.** Se clicar num lançamento navega para outra página, auditar 20 lançamentos é 40 navegações — e a pessoa volta pro Excel.

```
┌─────────────────────────────────┬──────────────────────────┐
│ lista continua visível e ativa  │ painel 480px             │
│ ─────────────────────────────── │ ──────────────────────── │
│  14/03  RIO2A18   R$ 1.372,00 ◀ │ Abastecimento            │
│  14/03  BRA2E19   R$   890,00   │ 14/03/2026 08:22         │
│  15/03  RIO2A18   R$ 1.510,00   │                          │
│  ...                            │ [campos]                 │
│                                 │                          │
│  ↑↓ navega e o painel acompanha │ [Salvar]  [⋯]            │
└─────────────────────────────────┴──────────────────────────┘
```

- Painel de 480px à direita. A lista **encolhe**, não some.
- Linha selecionada: `bg-brand-50` + barra de 2px `brand-700` à esquerda.
- `↑` `↓` navegam a lista **com o painel acompanhando**. É assim que se audita.
- `Esc` fecha. A lista volta a ocupar tudo, com a linha ainda marcada.
- Abaixo de 1280px o painel vira slide-over sobreposto.
- Modal: **só** para confirmação destrutiva. Nada com mais de 3 campos vira modal.

## Teclado

Desktop merece teclado. Quem lança 40 abastecimentos não usa o mouse.

| Tecla | Ação |
| --- | --- |
| `⌘K` / `Ctrl+K` | busca global — placa, motorista, nº de CT-e, chave |
| `/` | foca o filtro da tela |
| `↑` `↓` | navega as linhas |
| `Enter` | abre a linha em foco |
| `Esc` | fecha painel / cancela edição |
| `Space` | marca a linha |
| `Tab` | próximo campo na edição inline |
| `[` | recolhe a sidebar |
| `n` | novo registro da tela atual |
| `?` | atalhos |

Linha em foco pelo teclado tem `ring-2 ring-brand-500 ring-inset` — visível, diferente de hover.

## Filtros

- **Inline, live, no topo da tabela.** Nunca modal. Nunca "Aplicar filtros".
- Filtro ativo vira chip removível: `Veículo: RIO2A18 ✕`.
- Estado dos filtros na URL. Sempre. O link tem que ser compartilhável.
- Padrão de período: mês corrente. Nunca "todos" — 6 meses de dado carregado por engano é uma tela travada.
- `Limpar` aparece só quando há filtro.

## As quatro telas que definem o produto

### 1. DRE Veicular — a razão do sistema existir

```
┌────────────────────────────────────────────────┬──────────────────────┐
│ RIO2A18 · Scania R450 · março/2026             │ Detalhe da linha     │
│                                                │ ──────────────────── │
│ ┌────────────── A RÉGUA ──────────────────────┐│ Combustível          │
│ │ custo   ███████████████▊ 3,95              ││ R$ 14.850,00         │
│ │ receita █████████████████▊ 4,37            ││                      │
│ │                        ╿ equilíbrio 3,78   ││ 14/03  245,3 L  1.372│
│ │                        └── margem +0,42/km ││ 18/03  238,1 L  1.331│
│ └────────────────────────────────────────────┘│ 22/03  251,0 L  1.404│
│                                                │ ...                  │
│ km 10.243 · R$/km 4,37 · km/l 2,43 · eq. 8.680│                      │
│ ─────────────────────────────────────────────  │ → abre o abastecimento│
│ RECEITA BRUTA                      45.200,00 ▸ │                      │
│   Receita de fretes                45.200,00   │                      │
│ (-) DEDUÇÕES                       −1.492,00 ▸ │                      │
│ = RECEITA LÍQUIDA                  43.708,00   │                      │
│ (-) CUSTOS VARIÁVEIS              −25.250,00 ▾ │                      │
│   Combustível                     −14.850,00 ◀ │                      │
│   Arla 32                             −620,00  │                      │
│   ...                                          │                      │
│ = MARGEM DE CONTRIBUIÇÃO           18.458,00   │                      │
│ ...                                            │                      │
│ = RESULTADO LÍQUIDO                 2.120,00   │                      │
└────────────────────────────────────────────────┴──────────────────────┘
```

- **Régua no topo.** É o resumo. Quem só olha ela já decidiu.
- Grupo colapsável; **toda linha abre o drill-down à direita** — os lançamentos que a compõem, cada um linkando para a origem. Sem isso o relatório não é auditável e ninguém confia nele.
- `= LINHAS DE TOTAL` em `font-display font-semibold`, com filete acima. Subtotal em `text-sm`.
- Resultado final: `font-display text-2xl`, `success-700` ou `danger-700`, **com sinal**.
- Colunas `Valor` / `%RL` / `R$/km`, todas `w-28 font-mono tabular text-right`.
- Rateio mostra o critério na tela: *"Rateio por km — este veículo rodou 24,3% dos km da frota."* Número sem explicação é número em que não se confia.

### 2. Comparativo da frota — a tela que vende o sistema

Uma linha por veículo. Ordenável por resultado. **Ordene ascendente e o veículo que dá prejuízo aparece no topo.** Esse é o momento "aha".

```
Placa      Conjunto          km      R$/km   Custo/km   km/l    Resultado
▐BRA2E19▌  + carreta      8.120       3,12       3,44   2,11    −2.596,00  ▂▂▂▃▁
▐RIO2A18▌  + 2 carretas  10.243       4,37       3,95   2,43     2.120,00  ▄▅▄▆▅
▐MGA4F21▌  solo           6.890       4,90       3,80   3,02     7.580,00  ▆▆▇▆▇
```

- Régua compacta como célula, não número solto.
- Sparkline de 6 meses na última coluna. Tendência importa mais que o mês.
- Negativo em `danger-700`, com sinal, **e** a régua invertida. Cor nunca sozinha.
- Clique → DRE daquele veículo.

### 3. Fluxo de caixa — matriz dia × conta

- **Primeira coluna sticky** (`sticky left-0`) + cabeçalho sticky. Sem os dois, a matriz é inútil.
- Linha de **saldo acumulado** com destaque; saldo projetado negativo em `danger-50` de fundo. É por isso que a pessoa abriu a tela.
- Toggle **"Considerar previstos"** bem visível. É a função mais valiosa: mostra se o dinheiro acaba dia 20.
- Dia expansível → lançamentos do dia, inline, sem sair.
- `paid_at` editável inline. Dar baixa é tarefa de repetição.

### 4. Importar CT-e

- Drop zone ocupando a área toda, aceitando 40 arquivos ou um ZIP.
- Progresso **por arquivo**, não barra global: `12 importados · 3 atualizados · 1 erro`.
- Erro é linha na lista com a mensagem específica e o botão de ação. Nunca um toast que some.
- Ao final: agrupamento sugerido por (origem, destino, dia) → "Criar 4 viagens". Ação em lote, não 12 formulários.

## Estados

| Estado | Desktop |
| --- | --- |
| Carregando | skeleton com a geometria da tabela |
| Vazio (sem filtro) | o que é a tela + por que está vazia + botão. Sem ilustração. |
| Vazio (com filtro) | "Nenhum abastecimento com esses filtros." + `[Limpar filtros]` |
| Erro | inline, no lugar do conteúdo, com `[Tentar de novo]` |
| Salvando | `opacity-60` + botão em loading. Nunca bloquear a tela inteira. |
| Sem permissão | a ação não existe. Não mostre desabilitado. |

## O que não fazer no desktop

- **Não centralize conteúdo em coluna estreita.** Isto não é um blog.
- **Não use modal para formulário.** Slide-over ou master-detail.
- **Não pagine 25 em 25** o que cabe rolando.
- **Não esconda ação atrás de hover.** A pessoa tem que ver que existe.
- **Não faça card de 300px** com três números dentro. Isso é tabela.
- **Não anime transição de página.** 200ms × 400 navegações por dia = 80 segundos de espera inventada.
- **Não recarregue a página inteira** ao filtrar. `wire:model.live` com debounce de 300ms.
