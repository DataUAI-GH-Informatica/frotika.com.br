# ADR-002 — Painel da plataforma e cobrança centralizada no dono do sistema

- **Status:** aceito
- **Data:** 2026-07-18
- **Relaciona-se com:** seção 11 do blueprint (Painel da plataforma) e Fase 8

## Contexto

A primeira implementação de billing colocou a gestão de licenças do lado do **cliente**: a tela `/assinatura` (`billing.licenses.*`) permitia que o `owner` do grupo, na empresa principal, emitisse e desse baixa nos próprios boletos, autorizado pelo gate `manage-company-licenses`.

Isso inverte o modelo de negócio: quem cobra é o **dono do sistema Frotika**, não o cliente. O cliente apenas paga o boleto que recebe. O blueprint já previa um painel da plataforma (`/admin`, `App\Platform\**`, `is_platform_admin`), mas ele ainda não existia no código.

## Decisão

1. **Acesso à plataforma** é controlado por `users.is_platform_admin` (flag), verificado pelo middleware `EnsurePlatformAdmin` e pelo gate `access-platform`. A conta do dono também tem um `Group` com `type = 'platform'` (enum `App\Domain\Tenancy\Enums\GroupType`), criado pelo `PlatformAdminSeeder`. O command `frotika:promote-platform-admin {email}` promove uma conta existente.

2. **Emissão e baixa de boletos passam a ser exclusivas do painel `/admin`** (`App\Platform\Http\Controllers`). As Actions `IssueManualCompanyLicenseInvoice` e `MarkCompanyLicenseInvoiceAsPaid` passam a autorizar por `access-platform` em vez de `manage-company-licenses` (gate removido).

3. **O lado do cliente vira somente-leitura.** A tela `/assinatura` e seus controllers/requests foram removidos. O cliente continua vendo o boleto para pagar através do banner de licença no layout. O middleware `EnsureCompanyLicenseAllowsWrite` deixa de isentar `billing.licenses.*` e passa a redirecionar bloqueios para o `dashboard`.

## Consequências

- Os models `CompanyLicense`, `CompanyLicenseInvoice`, `Company` e `Group` não usam `CompanyScope`, então o painel os consulta filtrando por `group_id`/`company_id` sem violar a regra 4 do AGENTS.md (nenhum `withoutGlobalScope` é necessário).
- Escopo enxuto: o painel lista grupos/empresas e lança/baixa boletos. Métricas completas (MRR/churn), gestão de planos, saúde do sistema e impersonação (seção 11.3) ficam para uma iteração futura da Fase 8, sobre a mesma base `App\Platform`.
- O boleto continua **manual** (linha digitável e URLs informadas no formulário); não há integração com gateway.
