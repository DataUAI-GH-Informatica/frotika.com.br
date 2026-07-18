# ADR-003 — Licença por grupo e cadastro de empresas pelo cliente

- **Status:** aceito
- **Data:** 2026-07-18
- **Relaciona-se com:** ADR-002, seção 3.1 do blueprint (Group/Company/User) e seção 11 (painel da plataforma)

## Contexto

A implementação inicial de cobrança (ADR-002) modelou a licença **por empresa** (`company_licenses`, com `company_id` único e `is_primary`), e o `CompanyObserver` criava uma licença a cada empresa criada. Havia ainda um model `Subscription` por grupo, ocioso (não consumido por painel, middleware ou banner).

Isso conflita com a regra 3.1.2 do blueprint: **assinatura, plano e fatura pertencem ao `Group`, não à `Company` — um grupo com 3 empresas tem 1 assinatura.** Ao abrir o módulo de cadastro de empresas pelo cliente, a modelagem por empresa geraria uma licença/cobrança nova a cada CNPJ, o que não é o desejado.

## Decisão

1. **A licença é do grupo.** As tabelas `company_licenses`/`company_license_invoices` foram substituídas por `group_licenses` (uma linha por grupo, `group_id` único, sem `company_id`/`is_primary`) e `group_license_invoices` (FK `group_license_id`). Models, enums (`GroupLicenseStatus`, `GroupLicenseInvoiceStatus`), Actions (`RegisterGroupLicense`, `IssueGroupLicenseInvoice`, `MarkGroupLicenseInvoiceAsPaid`) e as chaves de config (`billing.group_license_*`) foram renomeados. As migrations foram reescritas (ainda não haviam ido para produção).

2. **`Subscription` foi removido.** Existe um único conceito de cobrança por grupo: a `GroupLicense`. O onboarding (`RegisterOwnerAndCompany`) cria a licença do grupo via `RegisterGroupLicense`; o `CompanyObserver` deixou de criar licença e apenas define `groups.primary_company_id` quando ainda não há empresa principal.

3. **O bloqueio de escrita passa a olhar a licença do grupo.** `EnsureCompanyLicenseAllowsWrite` virou `EnsureGroupLicenseAllowsWrite`, resolvendo a licença por `users.current_group_id`. O status JSON de bloqueio passou de `company_license_blocked` para `group_license_blocked`. O painel `/admin` lança/baixa boleto por `GroupLicense`, e o MRR soma a mensalidade das licenças ativas (uma por grupo).

4. **Cadastro de empresas pelo cliente (`/empresas`).** CRUD completo para `owner`/`admin` do grupo: listar, criar (com busca de CNPJ na Receita reaproveitando `LookupCnpjController`), editar e desativar (soft delete). Regras em `CompanyPolicy`; mutações em Actions (`CreateCompany`, `UpdateCompany`, `DeactivateCompany`). Criar empresa semeia o plano de contas base e a conta Caixa, **sem** gerar cobrança — a licença permanece a do grupo.

## Consequências

- Não é possível desativar a **empresa principal** do grupo nem a **empresa ativa** do usuário (guardas em `DeactivateCompany`); troque a ativa antes.
- As mutações de empresa ficam sob `EnsureGroupLicenseAllowsWrite`: se a licença do grupo estiver bloqueada, o cadastro/edição também bloqueia — coerente com "tudo sob a licença do grupo".
- ADR-002 permanece válido quanto ao painel e à cobrança centralizada; este ADR apenas troca a granularidade da licença (empresa → grupo) e adiciona o módulo de empresas.
- Integração com gateway de pagamento segue em aberto (seção 18 do blueprint); o boleto continua manual.
