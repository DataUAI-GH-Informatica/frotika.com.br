# Checklist de revisão de UI — Frotika

Rode a tela. Tire screenshot em **1440×900** (o uso principal) e **390×844**. Percorra item por item.
Qualquer ❌ é bloqueante.

## O teste dos 3 segundos

Apague mentalmente as palavras da tela. Sobrou o admin de um e-commerce?
→ **Refaça.** Não é ajuste, é direção errada.

## Anti-genérico

- [ ] Zero `shadow-*` fora de modal / popover / dropdown / bottom sheet
- [ ] Zero `rounded-xl`, `rounded-2xl`, `rounded-3xl`
- [ ] `rounded-full` só em avatar e badge pill
- [ ] Zero gradiente fora do fundo da sidebar
- [ ] Zero hex literal na Blade — tudo token do `@theme`
- [ ] No máximo **um** elemento chamativo na tela
- [ ] Nenhum ícone puramente decorativo ao lado de título

## Densidade (desktop)

- [ ] Linha de tabela tem **36px** no desktop, não 44, 56 nem 64
- [ ] **Contei as linhas visíveis em 1440×900: são ≥16**
- [ ] Célula é `py-1.5 px-3`
- [ ] Gap entre seções é 24px, não 48
- [ ] Nenhum card com padding maior que 16px
- [ ] Tabela é fluida, sem `max-w`; formulário é `max-w-3xl`

## Desktop — o uso principal

- [ ] Cabeçalho da tabela é sticky
- [ ] Ordenação por clique no cabeçalho, refletida na URL
- [ ] Coluna numérica tem largura fixa (não dança ao filtrar)
- [ ] Tabela financeira tem linha de totais sticky no rodapé
- [ ] Seleção em lote existe, com barra de ação **no rodapé**
- [ ] Clicar na linha abre master-detail — **não navega para outra página**
- [ ] `↑` `↓` navegam a lista com o painel acompanhando
- [ ] `Esc` fecha o painel, `/` foca o filtro, `⌘K` abre a busca
- [ ] Linha em foco por teclado tem anel visível, distinto de hover
- [ ] Filtros inline e live — **nenhum modal, nenhum "Aplicar"**
- [ ] Estado dos filtros na URL (link compartilhável)
- [ ] Carregando é skeleton com a geometria da tabela, não spinner
- [ ] Nenhum formulário em modal (só confirmação destrutiva)
- [ ] Nenhuma paginação abaixo de 500 registros
- [ ] Nenhuma transição animada de página

## Números

- [ ] Todo valor é `font-mono tabular text-right`
- [ ] `R$` está em `.unit` (85%, slate-400), o número não
- [ ] Colunas numéricas alinham na vírgula entre linhas
- [ ] Nenhum `number_format` solto — só `Format::*`

## Cor

- [ ] Tela com dados saudáveis está quase monocromática
- [ ] Toda cor semântica codifica um fato, não uma decoração
- [ ] `accent` usado como "olhe aqui", nunca como "aviso"
- [ ] Navy só em estrutura

## Mobile (390px) — o complemento

- [ ] Existe bottom nav; **não** é a sidebar virada drawer
- [ ] Ação primária está na faixa inferior da tela, nunca no topo
- [ ] Todo alvo de toque tem ≥ 44px, com ≥ 8px entre alvos
- [ ] Nenhuma tabela — viraram cards de 3 linhas
- [ ] Todo input tem `inputmode` correto
- [ ] Input tem `text-base` (16px) — senão o iOS dá zoom ao focar
- [ ] Rodapé fixo respeita `env(safe-area-inset-bottom)`
- [ ] Nenhuma afordância que só existe no `:hover`
- [ ] Nenhuma rolagem horizontal
- [ ] Menos de 5 opções → segmented control, não `<select>`

## Acessibilidade

- [ ] Foco visível pelo teclado em todo interativo
- [ ] Contraste AA (texto normal 4.5:1, ≥18px 3:1)
- [ ] Tabela navegável por teclado
- [ ] `prefers-reduced-motion` respeitado
- [ ] Cor não é o único portador de significado (negativo tem sinal, não só vermelho)

## Estado

- [ ] Estado vazio diz o que é a tela, por que está vazia, e traz o botão
- [ ] Erro diz o que houve e o que fazer, sem pedir desculpa
- [ ] Toda ação tem `wire:loading`
- [ ] Clique duplo não gera lançamento duplicado
- [ ] Confirmação destrutiva nomeia o registro e a consequência

## Texto

- [ ] Nenhum jargão contábil sem tradução ("competência" → "Data do serviço")
- [ ] Ação com o mesmo nome do início ao fim
- [ ] Nenhum "Enviar" / "Submeter" / "Nenhum registro encontrado"
