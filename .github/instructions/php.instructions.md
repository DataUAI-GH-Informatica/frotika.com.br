---
name: 'PHP / Laravel'
description: 'Convenções de PHP e Laravel do Frotika'
applyTo: '**/*.php'
---

# PHP no Frotika

- `declare(strict_types=1);` na primeira linha, sempre.
- `final` por padrão em Action, Service e DTO. Model e Livewire não são `final`.
- Tipagem explícita em todo parâmetro e retorno, inclusive `void`.
- Enum nativo backed por string para campo de enum. Sempre com `label(): string`:

```php
enum MaintenanceType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventiva',
            self::Corrective => 'Corretiva',
        };
    }
}
```

- Nunca `$model->update($request->all())`. FormRequest ou `rules()` do Livewire, campo a campo. Motivo: mass assignment em tabela com `company_id` é vetor de vazamento entre clientes.
- Escrita que toca mais de uma tabela vai dentro de `DB::transaction()`.
- `Gate::authorize` dentro da Action, não só na UI. Motivo: a mesma Action é chamada por job e por comando, onde não há UI para proteger.

## Tenancy

Todo model com dado de empresa usa `BelongsToCompany`:

```php
final class Fueling extends Model
{
    use BelongsToCompany, SoftDeletes;
}
```

`withoutGlobalScope(CompanyScope::class)` só em `app/Platform/**`.

Job carrega `company_id` explícito:

```php
public function __construct(public readonly int $companyId, public readonly string $xmlPath) {}

public function handle(TenantContext $tenant, CteImporter $importer): void
{
    $tenant->runFor(Company::withoutGlobalScopes()->findOrFail($this->companyId),
        fn () => $importer->import($this->xmlPath));
}
```

Motivo: o `TenantContext` vive no container e não é serializado na fila.
