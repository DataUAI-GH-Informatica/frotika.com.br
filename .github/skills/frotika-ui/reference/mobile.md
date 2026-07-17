# Mobile no Frotika

> **O uso principal do Frotika é desktop** — ver [desktop.md](./desktop.md). Este documento cobre o complemento: o motorista lançando no posto. Não invista aqui o esforço que o desktop merece.

**Mas mobile não é o desktop encolhido. É arquitetura de informação diferente.**

Se o seu plano mobile é "a sidebar vira drawer e as tabelas rolam de lado", pare e releia.

## Quem usa o celular, e para quê

| Persona | O que faz no celular | Onde está |
| --- | --- | --- |
| **Motorista** | Lança abastecimento, tira foto do cupom, abre manutenção | No posto, na oficina, com a mão suja de diesel, 1 barra de sinal, sol na tela |
| **Dono** | Confere saldo, olha alerta, abre o DRE de um veículo | No pátio, andando, entre uma coisa e outra |

Ninguém preenche cadastro completo no celular. Ninguém lê fluxo de caixa no celular. **Mobile é lançar e consultar.** Cadastro e análise são desktop.

Isso define o que otimizar: **lançar abastecimento em menos de 40 segundos, com uma mão.**

## Breakpoints

| Faixa | Nome | Navegação | Listagens |
| --- | --- | --- | --- |
| `< 768px` | mobile | **bottom nav + FAB** | cards |
| `768–1023px` | tablet | sidebar recolhida (72px) | tabela reduzida |
| `≥ 1024px` | desktop | sidebar expandida (264px) | tabela completa |

Três faixas. Não invente uma quarta.

## Arquitetura mobile

```
┌─────────────────────────────┐
│ ← Abastecimentos      🔔 👤 │  topbar 56px — contexto, não ação
├─────────────────────────────┤
│                             │
│  [conteúdo, rolagem]        │
│                             │
│                             │
│         zona de leitura     │
│  ·  ·  ·  ·  ·  ·  ·  ·  ·  │
│         zona do polegar     │  ← ação primária SEMPRE aqui
│                             │
│                        ╭───╮│
│                        │ + ││  FAB, se a tela não tem barra de ação
│                        ╰───╯│
├─────────────────────────────┤
│  ⌂      🚚     ⊕     📊   ⋯ │  bottom nav 56px + safe-area
│ Início Viagens  +   Frota Mais│
└─────────────────────────────┘
```

### Bottom nav — 4 destinos + 1 ação

| Item | Destino |
| --- | --- |
| **Início** | saldo, alertas, régua da frota |
| **Viagens** | lista de viagens, importar CT-e |
| **⊕** | **não é destino** — abre o sheet de lançamento |
| **Frota** | veículos → DRE do veículo |
| **Mais** | financeiro, motoristas, configurações |

O `⊕` central abre um bottom sheet: `Abastecimento` · `Manutenção` · `Viagem` · `Lançamento`. Abastecimento é o primeiro porque é 70% do uso.

Item ativo: ícone `brand-700` + rótulo `brand-700` 11px. Inativo: `slate-400`. Sem badge, sem animação.

## Thumb zone — a regra que mais se viola

O celular é segurado com uma mão. O polegar alcança confortavelmente **o terço inferior**.

- **Ação primária SEMPRE na faixa inferior.** Nunca "Salvar" no topo.
- Formulário tem **barra de ação fixa no rodapé**, acima da safe area, com o botão primário ocupando a largura toda.
- O topo é para contexto e leitura. Única ação permitida lá: voltar.
- Ação destrutiva nunca fica na zona do polegar — vai para o menu `⋯`. Motivo: o que o polegar alcança fácil, ele aperta sem querer.

```html
<!-- Barra de ação fixa. Padrão de todo formulário mobile. -->
<div class="fixed inset-x-0 bottom-0 border-t border-slate-200 bg-white p-3 safe-b lg:static lg:border-0 lg:bg-transparent lg:p-0">
    <button class="h-12 w-full rounded-md bg-brand-700 font-medium text-white lg:h-9 lg:w-auto lg:px-4">
        Salvar abastecimento
    </button>
</div>
```

## Alvos de toque

- Mínimo **44×44px**. Preferido **48px**.
- **8px de gap entre alvos.** Dois botões colados = toque errado.
- Linha de tabela vira card com ≥72px de altura.
- Ícone de 20px dentro de um alvo de 44px — o alvo é maior que o desenho.

## Formulários

A regra que muda tudo: **o formulário segue a ordem física do cupom, não a ordem do banco de dados.**

O cupom do posto traz três números: **odômetro, litros, valor total**. Nessa ordem. Preço por litro é *calculado*, não digitado — ninguém digita 3 casas decimais de pé num posto.

```
Abastecimento
─────────────────────────
Veículo      [chip de placa ▾]     ← pré-selecionado: último usado
Data/hora    [14/03/2026 08:22]    ← pré-preenchido: agora
─────────────────────────
Odômetro     [        418.900] km
Litros       [        245,300] L
Valor total  [      R$ 1.372,00]
             ↳ R$ 5,594/L · 2,43 km/l    ← calculado ao vivo, cinza
─────────────────────────
Tanque cheio [ Sim ] [ Não ]       ← segmented, não select. Sim é o padrão.
Produto      [Diesel S10 ▾]
─────────────────────────
📷 Foto do cupom                    ← alvo de 96px. A mão está suja.
─────────────────────────
[ Salvar abastecimento ]           ← fixo no rodapé
```

### Regras

- **Um campo por linha.** Sempre. Nunca dois lado a lado em mobile.
- `inputmode` correto ou o teclado errado abre:
  - litros, valor → `inputmode="decimal"`
  - odômetro → `inputmode="numeric"`
  - telefone → `inputmode="tel"`
  - e-mail → `inputmode="email"`
- `enterkeyhint="next"` nos campos, `"done"` no último.
- `text-base` (16px) em todo input. Abaixo disso o **iOS dá zoom ao focar** e o layout quebra.
- **Menos de 5 opções → segmented control**, não `<select>`. Motivo: select em mobile abre um picker de tela cheia para escolher entre "Sim" e "Não".
- Data → `type="date"` nativo. Não construa date picker.
- Foto → `capture="environment"`, alvo grande, preview imediato.
- **Não autofocar em mobile.** Abre o teclado e engole o contexto antes de a pessoa ler a tela.
- Pré-preencha o que dá: veículo = último usado por aquele usuário, data = agora, motorista = vínculo vigente do veículo.
- Cálculo ao vivo, em cinza, abaixo do campo. É a conferência do usuário contra o cupom.

## Tabela vira card

Tabela não existe abaixo de 768px. Rolagem horizontal é falha, não solução.

Anatomia de 3 linhas — identidade + valor / métrica / contexto:

```
┌──────────────────────────────────────┐
│ ▐RIO2A18▌                R$ 1.372,00 │  ← chip + valor (mono, direita)
│ 245,300 L · 2,43 km/l                │  ← a métrica que importa
│ 14/03 · Posto Serra · João           │  ← contexto, slate-400, 12px
└──────────────────────────────────────┘
```

- Linha 1: o que é + quanto custou. Nada mais.
- Linha 2: o número que a pessoa veio ver.
- Linha 3: o resto, apagado.
- Card inteiro é o alvo. Sem botão "ver".
- Ação secundária: swipe ou `⋯` à direita. Nunca uma fileira de ícones.

## Telas específicas

| Tela | Desktop | Mobile |
| --- | --- | --- |
| **DRE do veículo** | cascata + tabela | **régua no topo** + linhas colapsáveis + drill-down em bottom sheet |
| **Fluxo de caixa** | matriz dia × conta | lista por dia, saldo acumulado sticky no topo. A matriz não cabe — não tente. |
| **Comparativo da frota** | tabela ordenável | cards com régua, ordenados por resultado. O pior primeiro. |
| **Importar CT-e** | drag & drop múltiplo | seletor de arquivo simples. Ninguém importa 40 XMLs do celular. |
| **Cadastro de veículo** | formulário completo | disponível, mas sem otimização. Não é o caso de uso. |

## Sinal fraco é a regra, não a exceção

O motorista está num posto de beira de estrada. Isso não é caso de borda.

- **Rascunho local.** Formulário não perde dado ao perder conexão. Salve em `sessionStorage` a cada mudança, limpe no sucesso.
- **Fila de envio.** Salvar com sinal ruim não falha em silêncio: entra em fila, mostra "Enviando…", tenta de novo.
- **Foto sobe em background**, com retry. O lançamento salva sem esperar a foto.
- **Nunca um spinner infinito.** Timeout de 15s → mensagem com ação: "Sem conexão. Salvamos aqui e enviamos quando voltar."

Isso é requisito, não melhoria futura. Um lançamento perdido no posto é um lançamento que nunca mais acontece.

## Detalhes que quebram na prática

- `<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">` — sem `viewport-fit=cover` a safe area não funciona.
- `env(safe-area-inset-bottom)` em todo elemento fixo no rodapé.
- Sol na tela: contraste AA é piso, não meta. Cinza claro em fundo branco some no pátio.
- Nenhuma afordância só no `:hover`. Não existe hover.
- `touch-action: manipulation` nos alvos — mata o delay de 300ms do double-tap-to-zoom.
- Sem rolagem horizontal em nenhuma tela. Nenhuma.
