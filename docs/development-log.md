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
- Exposto endpoint HTTP `POST /registrar` para acionar o onboarding minimo via web.
- Adicionado teste de sucesso HTTP (201) e teste de validacao HTTP (422) para o endpoint.
- Extraida a logica do endpoint para controller dedicado `RegisterOwnerAndCompanyController`.
- Validacao do endpoint movida para Form Request dedicado `RegisterOwnerAndCompanyRequest`.
- Validacao fortalecida com unicidade de e-mail/CNPJ e normalizacao de entrada (email/cnpj/tax_regime) no Form Request.
- Cobertura de testes HTTP ampliada para conflitos de unicidade: e-mail existente e CNPJ existente (422).
- Mensagens de validacao padronizadas em pt-BR no Form Request de onboarding.
- Testes HTTP passam a validar explicitamente os textos de erro em pt-BR no JSON de resposta.
- Onboarding passa a criar assinatura inicial em status `trialing` (14 dias) na mesma transacao.
- Onboarding passa a criar conta bancaria padrao da empresa (`Caixa`, tipo `cash`, saldo inicial e atual zerados, `is_default = true`) na mesma transacao.
- Onboarding passa a semear o plano de contas padrao da empresa (`financial_categories`) com estrutura hierarquica e categorias de sistema obrigatorias.
- Categoria financeira passa a usar enums nativos (`type`, `dre_group`, `allocation`) com cast no model para tipagem forte.
- Adicionado teste dedicado da action `SeedDefaultFinancialCategories` cobrindo hierarquia, isolamento entre empresas e casts de enum.
- Criada migration inicial de `financial_entries` com os dois eixos de data (`competence_date` e `paid_at`), campos centrais e indices principais para fluxo de caixa e DRE.

### Validacoes da etapa 0.4

- `vendor/bin/phpunit tests/Feature/Tenancy/RegisterOwnerAndCompanyActionTest.php`
- `vendor/bin/pint --dirty`
- `composer test`
