# ADR-007 — Observabilidade (Telescope), filas (Horizon) e websocket (Reverb)

- **Status:** aceito
- **Data:** 2026-07-18
- **Relaciona-se com:** seção 2.1 do blueprint (Dependências), seção 3.3 (Filas e tenancy) e ADR-002 (painel da plataforma)

## Contexto

Com um servidor dedicado disponível, o projeto passa a ter infraestrutura para operar com robustez em produção:

1. **Diagnóstico de erros em produção** — hoje só há log em arquivo (`LOG_STACK=single`). Investigar um erro exige acesso ao servidor e leitura de log cru.
2. **Visibilidade das filas** — o import de CT-e é assíncrono (ADR-007 do blueprint) e outros processamentos virão. Sem um painel, não há como ver throughput, jobs falhos ou filas travadas.
3. **Tempo real** — a intenção é mover processamentos pesados para fila e **notificar o usuário quando terminarem**, o que exige um transporte de websocket.

O blueprint (2.1) já previa `laravel/horizon` (produção) e `laravel/telescope` (dev). **Reverb não estava previsto** — esta é a extensão registrada aqui.

## Decisão

### 1. Telescope como dependência de produção, desligado por padrão

Ao contrário do blueprint (que o listava como `--dev`), o Telescope entra como dependência **normal** (`require`), porque o objetivo é **ativá-lo sob demanda em produção** para depurar um incidente. Salvaguardas:

- Não é auto-descoberto (`extra.laravel.dont-discover` no `composer.json`); os providers só são registrados quando `config('telescope.enabled')` é verdadeiro. Assim, quando desligado, não há custo de observação nem rotas expostas.
- Controlado por `TELESCOPE_ENABLED` (default `false`). Liga-se editando o `.env` e limpando o cache de config.
- Painel em `/admin/telescope`, com o gate `viewTelescope` restrito ao **admin da plataforma** (`isPlatformAdmin()`), coerente com o ADR-002. Fora de `local`, parâmetros e cabeçalhos sensíveis são ocultados e só exceções/requisições/jobs falhos são gravados.

### 2. Horizon para as filas, sobre Redis

- Filas de **produção** usam a conexão `redis`, supervisionadas pelo Horizon (`php artisan horizon`). O painel fica em `/admin/horizon`, gate `viewHorizon` restrito ao admin da plataforma.
- **Desenvolvimento continua em `database`** (`QUEUE_CONNECTION=database`). O Horizon depende de `ext-pcntl`/`ext-posix` e **não roda em Windows** — por isso o pacote foi instalado com `--ignore-platform-req` para não travar o ambiente local, mas o processo só sobe no servidor Linux.
- A regra de tenancy da seção 3.3 permanece inalterada: todo job carrega `company_id` explícito e abre `TenantContext::runFor()`. Horizon é só o supervisor; não muda o contrato dos jobs.

### 3. Reverb como servidor de websocket

- `BROADCAST_CONNECTION=reverb`. O servidor sobe com `php artisan reverb:start` (incluído no `composer dev`; roda em Windows, pois usa ReactPHP e não depende de pcntl).
- Front com `laravel-echo` + `pusher-js` (`resources/js/echo.js`), credenciais por ambiente via `REVERB_*` / `VITE_REVERB_*`.
- **Canais respeitam o isolamento multi-tenant** (`routes/channels.php`):
  - `App.Models.User.{id}` — privado, cada usuário só escuta o próprio canal (base para "seu processamento terminou").
  - `company.{companyId}` — privado, autorizado por `user->companies()->whereKey()`, sem remover global scope (regra 4 do AGENTS.md preservada).
- Escala horizontal opcional via Redis (`REVERB_SCALING_ENABLED`).

## Consequências

- **Produção passa a exigir Redis** (filas + Horizon + escala do Reverb) e um processo supervisionado para Horizon e Reverb (Supervisor/systemd).
- Os três painéis vivem sob `/admin`, atrás do admin da plataforma — nenhum cliente os acessa.
- Telescope grava em banco (`telescope_entries`); manter o `telescope:prune` agendado quando ligado por período prolongado.
- As notificações em tempo real ainda não existem: esta é a **fundação**. O primeiro caso de uso (import de CT-e concluído) será implementado sobre estes canais em iteração futura.
- Instalar Horizon/Reverb localmente exige `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix` no Composer enquanto o ambiente for Windows.
