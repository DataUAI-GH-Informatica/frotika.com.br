---
description: 'Inicia uma fase do roadmap do Frotika'
---

Implemente a fase indicada do roadmap de [docs/frotika-blueprint.md](../../docs/frotika-blueprint.md).

Antes de escrever código:

1. Leia a seção 16 e localize a fase. Leia todas as seções que ela referencia.
2. Confirme que a fase anterior está com os testes verdes (`composer test`).
3. Apresente o plano e o checklist da fase. **Espere aprovação.**

Depois de aprovado:

4. Escreva os testes antes da implementação, para tudo que envolva cálculo.
5. Implemente item a item do checklist, rodando `composer test` a cada item.
6. Ao final, rode `vendor/bin/pint` e `vendor/bin/phpstan analyse`.
7. Reporte o que ficou fora e por quê.

Se a fase esbarrar em algum ponto da seção 18, pare e pergunte.
