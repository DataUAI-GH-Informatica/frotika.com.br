---
name: "Dinheiro e cálculo financeiro"
description: "Regras monetárias, rateio, DRE e fluxo de caixa"
applyTo: "app/Domain/Finance/**,app/Domain/Reports/**,app/Support/Money/**,database/migrations/**"
---

# Dinheiro no Frotika

O produto é um demonstrativo financeiro. Erro de 1 centavo aqui destrói a confiança no sistema inteiro.

## Armazenamento

- Valor monetário: `bigInteger`, em **centavos**, coluna terminando em `_cents`.
- Preço unitário com fração (R$/litro): `decimal(10,3)`.
- Nunca `float`. Nunca `decimal` para totais.

```php
// ✅
$table->bigInteger('total_cents');
$total = Money::ofMinor($cents, 'BRL');

// ❌
$table->decimal('total', 10, 2);
$total = $liters * $pricePerLiter;   // float
```

## Rateio

Sempre por **maior resto**, via `App\Support\Money\Apportionment`. A soma das partes é exatamente igual ao todo.

```php
// R$ 1.000,00 entre 3 veículos → [33333, 33333, 33334], soma = 100000
Apportionment::largestRemainder(100_000, [1, 1, 1]);
```

Divisor zero → rateio zero, com aviso na tela. Nunca divisão por zero silenciosa.

## As duas datas

| Campo             | Alimenta       | Rótulo na tela      |
| ----------------- | -------------- | ------------------- |
| `competence_date` | DRE            | "Data do serviço"   |
| `paid_at`         | Fluxo de caixa | "Data do pagamento" |

Nunca derive uma da outra. `paid_at = null` significa não realizado, não hoje.

As palavras "competência" e "regime de caixa" não aparecem na interface. O usuário é dono de transportadora, não contador.

## Agregação

Relatório agrega **só** `financial_entries`. Nunca soma direto de `fuelings`, `maintenances` ou `cte_documents` — essas geram lançamentos via `EntrySynchronizer`.

`affects_cashflow = false` na categoria mantém o valor fora do fluxo de caixa e dentro do DRE.

Consulta agregada única com `GROUP BY`. Nunca loop de categoria nem N+1 por veículo.

Ver seções 8 e 9 de [docs/frotika-blueprint.md](../../docs/frotika-blueprint.md).
