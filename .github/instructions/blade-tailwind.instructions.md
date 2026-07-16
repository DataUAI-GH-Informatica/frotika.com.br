---
name: 'Blade, Livewire e Tailwind'
description: 'Design system e regras de interface do Frotika'
applyTo: 'resources/**,app/Livewire/**'
---

# Interface do Frotika

Usuário: dono de transportadora, às 6h da manhã, que também vai trocar um pneu hoje. Não é usuário avançado.

## Tailwind v4

Tokens em `resources/css/app.css`, bloco `@theme`. **Nunca hex solto na Blade.**

- `brand-900` (`#002573`) é a cor da marca — estrutura: sidebar, cabeçalho, logo.
- `brand-700` é o botão primário. Motivo: a `900` é escura demais para superfície clicável com texto legível.
- `accent-500` (âmbar) é "olhe aqui" — atalhos, chip de placa. **Não é cor de aviso**; aviso é `warning`.
- `success` / `warning` / `danger` / `info` só com significado semântico.

## Componentes

Use `resources/views/components/ui/*`. Se não existe, crie lá — não faça markup solto na página.

Todo veículo aparece como `<x-ui.plate-chip>`, nunca como texto puro. É o átomo de identidade visual do sistema.

## Regras

- Coluna numérica: `font-mono`, `text-right`, classe `tabular`. Motivo: sem `tabular-nums` a coluna de valores não é lida, é decifrada.
- Sem query em Blade. Tudo resolvido no componente Livewire.
- Livewire não contém regra de negócio: valida, chama a Action, trata o resultado.
- Toda ação tem estado de carregamento (`wire:loading`) e é imune a clique duplo. Motivo: duplo clique gerando lançamento duplicado é bug financeiro.
- Fonte: `font-display` (Archivo) só em título de página, valor de card e cabeçalho do DRE. Nunca em rótulo de formulário.

## Texto

- Estado vazio nunca é "Nenhum registro encontrado". Diz o que é a tela, por que está vazia, e traz o botão da ação.
- Erro diz o que aconteceu e o que fazer. Não pede desculpa, não é vago.
  - ❌ "Erro ao processar arquivo."
  - ✅ "Este XML é de uma NF-e, não de um CT-e. O Frotika importa CT-e modelo 57."
- Ação mantém o mesmo nome do início ao fim: botão "Importar CT-e" → toast "12 CT-es importados". Nunca "Enviar" ou "Submeter".
- Confirmação destrutiva nomeia o registro e a consequência.

## Piso de qualidade

Responsivo até 360px · foco visível pelo teclado · `prefers-reduced-motion` respeitado · contraste AA.

Ver seções 12 e 13 de [docs/frotika-blueprint.md](../../docs/frotika-blueprint.md).
