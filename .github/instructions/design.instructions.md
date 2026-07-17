---
name: 'Design e interface'
description: 'Aponta para a skill frotika-ui em qualquer trabalho de interface'
applyTo: 'resources/**,app/Livewire/**,**/*.blade.php,**/*.css'
---

# Interface do Frotika

**Carregue a skill [frotika-ui](../skills/frotika-ui/SKILL.md) antes de escrever qualquer markup.** Ela tem os tokens exatos, a anatomia de cada componente, a especificação mobile e o processo de crítica. Sem ela o resultado sai como admin panel genérico — já saiu uma vez.

Resumo do que ela impõe (o detalhe está lá):

- **Painel de instrumentos, não dashboard.** A referência é a cabine do caminhão, não o Notion.
- **O uso principal é desktop.** É lá que se concilia, importa, audita e decide. Otimize para o milésimo clique, não para o primeiro.
- **Zero sombra** fora de overlay. Elevação é border 1px + degrau de fundo.
- **Raio máximo 8px.** `rounded-xl`/`2xl`/`full` proibidos (exceto avatar e badge).
- **Linha de tabela 36px no desktop** (44 é alvo de toque, não medida de tela). Meta dura: ≥16 linhas em 1440×900.
- **Todo número é `font-mono tabular text-right`**, com `R$` em `.unit`.
- **Cor é dado.** Tela saudável é quase monocromática.
- **Um acento por tela.**
- **A tabela é o produto:** cabeçalho sticky, ordenação, seleção em lote, edição inline, teclado, totais sticky.
- **Master-detail, não navegação.** Clicar na linha abre painel de 480px; a lista encolhe, não some.
- **Mobile é IA diferente**, não media query: bottom nav, ação no polegar, tabela vira card.
- **Nunca hex na Blade.** Só token do `@theme`.

Antes de construir: plano com wireframe ASCII **desktop e mobile**, e a crítica da passada 2.
Depois de construir: screenshot em 1440×900 e 390×844 contra [qualidade.md](../skills/frotika-ui/reference/qualidade.md). Conte as linhas visíveis.

Toda listagem copia [exemplo-lista.blade.php](../skills/frotika-ui/reference/exemplo-lista.blade.php). Não invente estrutura de listagem.
