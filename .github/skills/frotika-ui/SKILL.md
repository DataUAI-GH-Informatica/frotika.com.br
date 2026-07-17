---
name: frotika-ui
description: Sistema de design do Frotika. Use SEMPRE que for criar ou alterar qualquer interface — página, componente Blade, componente Livewire, CSS, layout, tabela, formulário, gráfico ou tela mobile. Contém a direção visual, os tokens exatos, a anatomia dos componentes, a especificação desktop (o uso principal), a especificação mobile e o processo de crítica obrigatório. Sem esta skill o resultado sai como um admin panel genérico.
---

# Sistema de design do Frotika

## A tese

**Isto é um painel de instrumentos, não um dashboard.**

Quem usa é dono de micro transportadora, ou a pessoa que faz tudo no escritório. **Ela abre o sistema às 7h e fecha às 18h, num monitor.** Passa o dia lendo instrumentos — manômetro, marcador de combustível, tacógrafo, odômetro. Sabe ler mostrador. Não sabe ler DRE.

**O uso principal é desktop.** É lá que se concilia, importa, audita e decide. Mobile é o complemento: o motorista lançando abastecimento no posto. Otimizar a tela para o primeiro clique é errar o alvo — **otimize para o milésimo**.

A referência não é Notion, Linear ou Stripe. A referência é **o painel da cabine e a ficha de manutenção da oficina**: densidade alta, hierarquia brutal, filete em vez de sombra, número grande e legível de longe, zero ornamento.

Se a tela que você fizer pudesse ser o admin de um e-commerce trocando as palavras, **está errada**. Refaça.

## Regra de ouro

**A ousadia inteira do sistema mora em um lugar só: a régua de R$/km.** Tudo em volta é disciplina e silêncio. Se você está inventando um segundo elemento chamativo, corte.

## Os dois usos

| | Desktop — **o produto** | Mobile — o complemento |
| --- | --- | --- |
| Quem | escritório / dono, o dia inteiro | motorista, 40 segundos |
| O quê | concilia, importa, audita, decide | lança abastecimento, tira foto |
| Telas | DRE, comparativo, fluxo de caixa, import | lançamento, consulta rápida |
| Otimize | densidade, teclado, contexto preservado | polegar, uma mão, sinal fraco |
| Spec | [reference/desktop.md](./reference/desktop.md) | [reference/mobile.md](./reference/mobile.md) |

Toda tela é planejada nos dois tamanhos. **O peso do esforço é no desktop.**

## As 10 regras que impedem o resultado genérico

Cada uma existe porque o padrão do agente vai contra ela.

1. **Zero `shadow-*`.** Elevação é `border` de 1px + degrau de fundo. Sombra só em overlay que flutua de verdade: modal, popover, dropdown, bottom sheet. Motivo: sombra em card é o tique número um do admin genérico e não significa nada num painel.

2. **Raio máximo 8px.** `rounded-md` (6px) em interativo, `rounded-lg` (8px) em superfície, **`rounded-none` em célula de tabela**. Nunca `rounded-xl`, `rounded-2xl`, `rounded-full` — exceto avatar e badge pill.

3. **Densidade, não respiro.** Linha de tabela **36px no desktop** (44px só em touch — 44 é alvo de toque, não medida de tela). Célula `py-1.5 px-3`. Gap entre seções 24px, não 48. Padding de página `py-6`, não `py-12`. **Meta dura: ≥16 linhas em 1440×900.** Meça, não estime.

4. **O número é o herói.** Todo valor é `font-mono tabular text-right`. O `R$` é `text-[0.85em] text-slate-400`, o número é `text-slate-900`. Motivo: alinhar na vírgula é o que transforma coluna em instrumento.

5. **Cor é dado, não decoração.** Navy só em estrutura (sidebar, cabeçalho, logo). Semântica só quando codifica um fato: resultado negativo, vencido, CNH a expirar. **Tela de frota saudável é quase monocromática.** Se tem cor, é porque tem problema.

6. **Um acento por tela. No máximo.** `accent-500` é "olhe aqui", não "isto é bonito".

7. **Sem gradiente.** Exceto o fundo da sidebar. Sem glassmorphism, sem blur, sem mesh.

8. **Filete, não card.** Listas e grupos separados por `border-b border-slate-200`, não por cards empilhados com sombra. Card só quando o conteúdo é de fato uma unidade destacável.

9. **Ícone é 16px inline / 20px isolado, stroke 1.5.** Nunca ícone decorativo ao lado de título. Ícone só quando substitui ou reforça uma ação.

10. **A tabela é o produto.** 80% do tempo de tela é nela: cabeçalho sticky, ordenação por clique, seleção em lote, edição inline, teclado, totais sticky. Uma listagem sem isso é uma listagem medíocre, e o resto não compensa. Ver [reference/desktop.md](./reference/desktop.md).

## Tokens

Fonte da verdade: [reference/tokens.css](./reference/tokens.css). Copie inteiro para `resources/css/app.css`.

**Nunca escreva hex na Blade.** Se a cor que você quer não existe no `@theme`, ou você está errado, ou falta um token — e aí discuta antes de inventar.

### Cor

`brand` (âncora estrutural, base `brand-900` = `#002573`) · `accent` (âmbar de sinalização) · `success` `warning` `danger` `info` (semânticos) · `slate` (neutros).

- App bg `slate-50` → superfície `white` → inset `slate-50` → borda `slate-200`.
- Texto: `slate-900` principal · `slate-600` secundário · `slate-400` terciário e unidades.

### Tipografia

| Papel | Família | Onde |
| --- | --- | --- |
| Display | **Archivo** 600/700 | título de página, valor de card, cabeçalho do DRE. Só isso. |
| UI | **IBM Plex Sans** 400/500/600 | todo o resto |
| Dados | **IBM Plex Mono** 400/500 | valor, placa, km, litro, CNPJ, chave de CT-e |

Plex Sans e Plex Mono compartilham esqueleto: o mesmo valor lido num card e numa tabela é o mesmo objeto. Archivo é grotesca de sinalização — traz a nota de estrada só nos títulos.

Escala: `11 / 12 / 14 / 16 / 20 / 28 / 40`. Base 14. Nada abaixo de 11. **Input em mobile é 16px** — abaixo disso o iOS dá zoom ao focar.

## O elemento de assinatura: a régua de R$/km

Custo por km é o número que decide a vida da transportadora. Ele merece ser um instrumento, não uma linha de tabela.

A régua é uma barra horizontal com três marcas: **receita/km**, **custo/km**, e o **ponto de equilíbrio**. A faixa entre receita e custo é a margem — verde se positiva, vermelha se invertida.

```
R$/km   0                    2,00                  4,00                 6,00
        ├──────────────────────┼──────────────────────┼──────────────────────┤
custo   ████████████████████████████████▊ 3,95
receita ██████████████████████████████████████████▊ 4,37
                                         ╿ equilíbrio 3,78
                                         └── margem +0,42
```

Aparece em: cabeçalho do DRE do veículo · card de veículo na frota · linha do comparativo · home do mobile. **Uma linguagem visual para o único número que importa.** Implementação em [reference/exemplo-regua.blade.php](./reference/exemplo-regua.blade.php).

Chip de placa (padrão Mercosul) é o átomo de identidade: todo veículo aparece como `<x-ui.plate-chip>`, nunca como texto puro.

## Processo obrigatório

Não comece escrevendo Blade. Trabalhe em duas passadas.

### Passada 1 — plano

Antes de qualquer código, escreva (curto, no chat):

1. **Trabalho da tela** — uma frase. Qual pergunta ela responde?
2. **Wireframe ASCII** — desktop (1440px) primeiro, mobile (390px) depois. Os dois. Sempre.
3. **Densidade** — quantos registros cabem em 1440×900 sem rolar? Se for listagem, o alvo é ≥16.
4. **Onde a cor entra** — e por quê. Se a resposta for "para ficar bonito", tire.
5. **Ação primária** — qual é, e onde está em cada breakpoint.

### Passada 2 — crítica antes de construir

Releia o plano e responda:

- Se eu apagar as palavras, isso vira admin de e-commerce? → refaça.
- Tem sombra em card? Tem `rounded-xl`? Tem gradiente? → tire.
- Tem mais de um elemento chamativo? → escolha um.
- É listagem sem cabeçalho sticky, ordenação e seleção em lote? → releia [reference/desktop.md](./reference/desktop.md).
- Formulário em modal, ou clicar na linha navega para outra página? → é slide-over e master-detail.
- O mobile é o desktop com sidebar virando drawer? → releia [reference/mobile.md](./reference/mobile.md) e refaça.
- A ação primária no mobile está no topo? → move pro rodapé.

Só depois construa. Siga o plano revisado.

### Passada 3 — crítica depois de construir

Rode a tela e **olhe para ela**, em 1440×900 e 390×844. Conte as linhas visíveis no desktop. Compare contra [reference/qualidade.md](./reference/qualidade.md), item por item. Uma imagem vale mais que mil tokens.

## Referências

- [reference/tokens.css](./reference/tokens.css) — `@theme` completo, copiar inteiro
- [reference/desktop.md](./reference/desktop.md) — **o uso principal.** Shell, densidade, a tabela de dados completa, master-detail, teclado, e as 4 telas que definem o produto
- [reference/anatomia.md](./reference/anatomia.md) — anatomia exata de cada componente, com classes
- [reference/mobile.md](./reference/mobile.md) — o complemento: thumb zone, formulários, sinal fraco
- [reference/qualidade.md](./reference/qualidade.md) — checklist de revisão
- [reference/exemplo-lista.blade.php](./reference/exemplo-lista.blade.php) — **página de referência. Toda listagem copia esta.**
- [reference/exemplo-regua.blade.php](./reference/exemplo-regua.blade.php) — o elemento de assinatura

## Escrita

Palavra em interface tem uma função: fazer entender. Não é decoração.

- **Estado vazio** nunca é "Nenhum registro encontrado". Diz o que é a tela, por que está vazia, e traz o botão.
  > *"Nenhuma viagem ainda."* / *"Importe os XMLs dos seus CT-es e as viagens aparecem aqui, com a receita já preenchida."* / `[Importar CT-e]`
- **Erro** diz o que houve e o que fazer. Não pede desculpa, não é vago.
  > ❌ "Erro ao processar arquivo." ✅ "Este XML é de uma NF-e, não de um CT-e. O Frotika importa CT-e modelo 57."
- **Ação** mantém o nome do começo ao fim: botão "Importar CT-e" → toast "12 CT-es importados". Nunca "Enviar" ou "Submeter".
- **Sem jargão contábil.** "Competência" é **"Data do serviço"**. "Regime de caixa" não existe na tela.
- Voz ativa, sentence case, verbo direto.
