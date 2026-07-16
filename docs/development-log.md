# Diario de desenvolvimento

## 2026-07-15 - Etapa 0.1 (base monetaria)

### Entregas da etapa 0.1

- Implementado rateio por maior resto em `App\Support\Money\Apportionment`.
- Cobertura de cenarios obrigatorios: divisao por 3, 7, 1 e 0, alem de distribuicao ponderada.

### Validacoes da etapa 0.1

- `vendor/bin/phpunit tests/Unit/Support/Money/ApportionmentTest.php`
- `vendor/bin/pint --dirty`
- `composer test`

## 2026-07-15 - Etapa 0.2 (money + cast + tenancy foundation)

### Entregas da etapa 0.2

- Criado value object `App\Support\Money\Money` em centavos, com soma, subtracao e integracao direta ao rateio.
- Criado cast `App\Casts\Money` para mapear colunas `_cents` para o value object.
- Criada fundacao de tenancy com `TenantContext`, `CompanyScope`, `BelongsToCompany`, `MissingTenantContextException`, `Group` e `Company`.
- `TenantContext` registrado como singleton no `AppServiceProvider`.
- Criado teste de isolamento para garantir que empresa A nao enxerga dados da empresa B.

### Validacoes da etapa 0.2

- `vendor/bin/phpunit tests/Unit/Support/Money/MoneyTest.php tests/Unit/Casts/MoneyCastTest.php tests/Feature/Tenancy/BelongsToCompanyIsolationTest.php`
- `vendor/bin/pint --dirty`
- `composer test`

### Proximos incrementos

- Conectar `TenantContext` ao middleware de request (`SetTenantContext`).
- Introduzir migrations oficiais de tenancy seguindo as convencoes do blueprint.
- Passar os testes de tenancy para schema real (PostgreSQL) quando a base de dominio estiver pronta.

## 2026-07-15 - Etapa 0.3 (middleware + schema inicial de tenancy)

### Entregas da etapa 0.3

- Criado middleware `App\Http\Middleware\SetTenantContext` para resolver tenant por sessao e fallback por usuario.
- Middleware registrado no grupo `web` no bootstrap da aplicacao.
- Criadas migrations iniciais de tenancy: `groups`, `companies`, `group_user`, `company_user`, `invitations` e colunas de tenant em `users`.
- Modelos `User`, `Group` e `Company` receberam relacoes de tenancy para suportar validacao de acesso por empresa.
- Teste de isolamento de `BelongsToCompany` atualizado para usar schema real de tenancy.
- Adicionado teste de integracao do middleware cobrindo sessao, fallback, 403 e request anonimo.

### Validacoes da etapa 0.3

- `vendor/bin/phpunit tests/Feature/Tenancy/SetTenantContextMiddlewareTest.php tests/Feature/Tenancy/BelongsToCompanyIsolationTest.php`
- `vendor/bin/pint --dirty`
- `composer test`

## 2026-07-15 - Etapa 0.4 (onboarding minimo transacional)

### Entregas da etapa 0.4

- Criada action `RegisterOwnerAndCompany` para criar usuario owner, grupo, empresa e vinculos em uma unica transacao.
- Criados DTOs `RegisterOwnerAndCompanyData` e `RegisterOwnerAndCompanyResult` para entrada/saida tipadas da action.
- Fluxo grava `current_group_id` e `current_company_id` no usuario para preparar bootstrap de sessao.
- Adicionado teste de sucesso do onboarding e teste de rollback completo em erro de CNPJ duplicado.

### Validacoes da etapa 0.4

- `vendor/bin/phpunit tests/Feature/Tenancy/RegisterOwnerAndCompanyActionTest.php`
- `vendor/bin/pint --dirty`
- `composer test`
