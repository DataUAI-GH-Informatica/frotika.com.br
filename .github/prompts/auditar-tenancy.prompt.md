---
description: 'Audita o isolamento entre empresas no Frotika'
---

Audite o isolamento multi-tenant do projeto. Não corrija ainda — reporte.

1. Liste todo model em `app/Domain/**/Models/` que tenha coluna `company_id` e **não** use a trait `BelongsToCompany`.
2. Liste toda ocorrência de `withoutGlobalScope` ou `withoutGlobalScopes` fora de `app/Platform/**`.
3. Liste todo job em `app/Jobs/**` e `app/Domain/**/Jobs/**` que toque dado de tenant e não receba `company_id` no construtor.
4. Liste toda migration com índice composto sobre tabela de tenant cujo índice **não** comece por `company_id`.
5. Liste todo model com `BelongsToCompany` que não tenha teste de isolamento correspondente em `tests/`.

Para cada achado: caminho, o que está errado, o que pode vazar na prática.

Ao final, um veredito: o isolamento está íntegro ou não.
