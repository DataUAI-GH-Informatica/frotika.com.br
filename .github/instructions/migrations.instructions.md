---
name: 'Migrations'
description: 'Convenções de schema do Frotika'
applyTo: 'database/migrations/**,database/factories/**,database/seeders/**'
---

# Schema no Frotika

- PostgreSQL 16. Pode usar `jsonb`, índice parcial e CTE recursiva.
- Toda tabela de cadastro ou lançamento tem `deleted_at` (soft delete).
- Toda tabela com dado de empresa tem `company_id` com FK e índice — **e o índice composto começa por `company_id`**. Motivo: toda query passa pelo global scope de tenant; índice que não começa por `company_id` não é usado.
- Valor monetário: `bigInteger` em centavos, sufixo `_cents`. Preço unitário: `decimal(10,3)`.
- Enum como `string` + cast para enum PHP. Não use o tipo enum nativo do banco. Motivo: adicionar um valor não exige migration.
- `timestamptz` para instante (`issued_at`, `fueled_at`), `date` para data de negócio (`competence_date`, `paid_at`).

Índice único parcial quando a unicidade é condicional:

```php
DB::statement('CREATE UNIQUE INDEX bank_accounts_default_unique
    ON bank_accounts (company_id) WHERE is_default AND deleted_at IS NULL');
```

Antes de alterar tabela existente: verifique se a migration original já foi para produção. Se não foi, edite a original em vez de criar uma nova.

Factory de model com `BelongsToCompany` não define `company_id` — a trait resolve pelo `TenantContext`. Nos testes, abra o contexto com `TenantContext::runFor()`.

Ver seção 5 de [docs/frotika-blueprint.md](../../docs/frotika-blueprint.md) para o schema completo.
