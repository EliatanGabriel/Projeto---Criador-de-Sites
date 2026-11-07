# ARCHITECTURE — WebStudio OS

**Versão:** 1.0 — Fase 0  
**Fonte:** Doc.md v1.0 §4, §6, §9, §12, §15

## 1. Visão de Arquitetura

Monólito modular (Doc.md §4.1). Aplicação principal única, organizada em domínios independentes por responsabilidade. Microsserviços **vedados** no MVP. Laravel concentra regras de negócio, autenticação, autorização, validação, banco de dados, processamento de demandas, geração de projetos, notificações, integrações e processos automatizados. React concentra interfaces administrativas, dashboard, formulários, tabelas, painéis, componentes interativos e navegação. Inertia.js integra as camadas **sem** API REST independente para todas as operações.

## 2. Compatibilidade e Versões (verificação 2026-11-07)

### 2.1 Stack aprovada

| Camada | Tecnologia | Status compatibilidade |
|---|---|---|
| Backend | Laravel 13 | Lançado 17/03/2026. Requer PHP 8.3–8.5, Composer 2.x, extensões: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, MbString, OpenSSL, PCRE, PDO, Tokenizer, XML. Suporte a Symfony 7.4/8.0. Bug fixes até Q3/2027, segurança até 17/03/2028. |
| Frontend | React 19 | Inertia v3 exige React ^19.0.0 e @types/react ^19.2. React 18 **não** suportado por `@inertiajs/react@^3.0`. |
| Integração | Inertia.js v3 | `inertiajs/inertia-laravel:^3.0` exige PHP ^8.2, Laravel `^11.35|^12.0|^13.0` — **compatível com Laravel 13**. Adapter React `@inertiajs/react:^3.0` exige React 19. v3 troca Axios por cliente XHR nativo, adiciona plugin Vite (`@inertiajs/vite`), `useHttp`, otimistic updates, layout props, enums PHP nativos em props. **Breaking:** republish config (`vendor:publish --provider="Inertia\ServiceProvider" --force`), `view:clear`, migrar interceptors Axios, `Inertia::lazy()` removido → `Inertia::optional()`, `router.cancel()` → `router.cancelAll()`, ESM only. |
| Linguagem FE | TypeScript ^5.9 | `@inertiajs/react` devDeps usam TS ^5.9.3. Inertia v3 suporta generics em `useForm`, tipos de erros e slots. Configurar `tsconfig` estrito. |
| Banco | PostgreSQL 10+ (pré-requisito Laravel); recomendado **16 ou 17** [PENDENTE confirmar versão no Fase 1] | Laravel 13 suporta PG 10+. PHP precisa de `pdo_pgsql` + `pgsql` + libpq (≥9.1; ≥10.0 com PHP 8.4). Para poolers (PgBouncer) Laravel 13 suporta `pooled` + `direct` (config `database.php` com sufixo `::direct`). Não usar driver externo salvo necessidade. |
| Build | Vite + Node LTS / Bun | Laravel 13 usa Vite por padrão. Inertia v3 inclui `@inertiajs/vite` (resolução de páginas + SSR opcional). SSR **não** é requisito do MVP (app administrativa interna); manter desabilitado por padrão. Prever `npm run dev` / `build`. |
| Estilização | Tailwind CSS (v4 [PENDENTE confirmar v4 vs v3]) | Compatível com React 19 + Vite. Sem conflito. |
| Autenticação | Laravel | **[PENDENTE]** Escolher Breeze vs Fortify vs nativo. Breeze já entrega Inertia + TS starter; Fortify entrega 2FA/API tokens. Não decidir nesta fase. |
| Autorização | Policies + Gates | **[PENDENTE]** Spatie Permission vs nativo. MVP com 3 usuários e 3 perfis poderia ser nativo (perfil em `users` ou policy por role), mas Spatie facilita evolução SaaS. Decisão em `DECISIONS.md`. |
| Arquivos | Disco `s3` (Laravel S3-compatível) | Requer `aws/aws-sdk-php` (via `league/flysystem-aws-s3-v3`, incluso no Laravel quando instalado). URLs privadas via temporárias assinadas. |
| Filas/Cache | Banco de dados ou Redis [PENDENTE] | Filas necessárias para notificações/geração de tarefas. MVP pode usar `database` driver para simplicidade; Redis recomendado se hospedagem suportar. Cache: `database`/`file` inicialmente. |
| Hospedagem | Render ou equivalente [PENDENTE final] | Critérios: suporte PHP 8.3/8.4/8.5, PostgreSQL gerenciado, S3-compatível, HTTPS, domínio próprio, backups, logs, filas persistentes, cron. Avaliar também Fly.io, Hetzner, Hostinger VPS. Não decidir nesta fase. |

### 2.2 Dependências mínimas (Fase 1 — não instalar agora)

**Composer:**
- `laravel/framework:^13.0` (PHP ^8.3)
- `inertiajs/inertia-laravel:^3.0`
- `laravel/breeze` **OU** `laravel/fortify` [a decidir]
- `spatie/laravel-permission` [se aprovado — condicional]
- `league/flysystem-aws-s3-v3` (arquivos)
- Dev: `pestphp/pest:^3.0` [recomendado] ou `phpunit/phpunit`, `laravel/pint`, `larastan/larastan`, `fakerphp/faker`, `mockery/mockery`

**NPM:**
- `react@^19.0.0`, `react-dom@^19.0.0`, `@inertiajs/react@^3.0`, `@inertiajs/vite@^3.0`, `typescript@^5.9`, `vite`, `tailwindcss` + `@tailwindcss/vite` (se v4), `axios` permanece opcional para integrações externas (v3 não o inclui por padrão)

**PHP extensions:** `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `dom`, `filter`, `hash`, `iconv`, `pcntl` (recomendado para SSR/queues), `redis` (se usar Redis), `zip` (exportações)

**Infra local:** PHP 8.3+ (preferir 8.4 por compatibilidade longeva), Composer 2, Node 20+ LTS, PostgreSQL local (Docker ou nativo), Git, GitHub, Docker opcional para dev [PENDENTE: Sail vs Herd vs nativo].

### 2.3 Riscos de compatibilidade

1. **Laravel 13 muito novo (março/2026):** pacotes de terceiros podem ainda não declarar suporte a `^13.0`. Mitigação: na Fase 1, verificar cada pacote no Packagist; postergar pacotes incompatíveis; preferir first-party.
2. **Inertia v3 breaking changes:** ESM only, sem Axios, sem `Inertia::lazy()`. Mitigação: seguir upgrade guide v3 desde o início; não portar código v2.
3. **React 19:** componentes de UI, hooks e bibliotecas de tabela/formulário devem suportar React 19. Mitigação: escolher biblioteca de componentes com suporte React 19 (pendente de definição).
4. **PHP 8.3 mínimo:** hospedagem dev/staging/prod devem rodar ≥8.3. Verificar Render e alternativas antes de escolher.
5. **Symfony 8:** Laravel 13 usa Symfony 8.0; pacotes que fixam Symfony 7 podem conflitar. Verificar com `composer why`.
6. **pgvector/AI:** Laravel 13 traz AI primitives e vector search; não utilizar no MVP para evitar dependência de extenção `pgvector`.

## 3. Estrutura de Domínios (Doc.md §4.2)

Domínios aprovados: `Authentication`, `Users`, `Clients`, `Commercial`, `Demands`, `Projects`, `Tasks`, `Briefings`, `Templates`, `Websites`, `Finance`, `Notifications`, `Settings`, `Reports`.

Cada domínio encapsula: Actions (casos de uso), Services, DTOs, Policies, Observers, Events/Listeners, Jobs, Notifications, Queries (consultas complexas) e Value Objects. Controllers devem ser finos (validar via FormRequest, autorizar via Policy, delegar a Action/Service, responder com Inertia). Models no `App\Models` ou por domínio conforme decisão de scaffold Laravel 13.

## 4. Fluxo Técnico Crítico — Conversão Demanda → Projeto

Sequência transacional (Doc.md §5 Módulo 09):

1. Controller recebe `ConvertDemandToProjectAction`.
2. Verificar Policy (apenas OWNER/Admin pode converter — Eliatan).
3. Verificar status da demanda = `aprovada` e ainda não convertida (constraint unique em `projects.demand_id`).
4. `DB::transaction()`:
   - Criar `projects` (cliente, demanda origem, tipo, escopo, template, responsável técnico, prioridade, prazos, URLs).
   - Criar `project_members`.
   - Gerar `project_tasks` a partir de `project_template_tasks` (ordem, título, descrição, prioridade, estimativa, responsável padrão).
   - Registrar `demand_status_history` → `convertida em projeto`.
   - Registrar `activity_logs` / `project_activities`.
   - Disparar `Notification` (projeto criado, tarefas atribuídas).
5. Rollback completo em caso de falha (testar com falha injetada).

Notificações de e-mail podem ser enfileiradas (`ShouldQueue`), mas o histórico interno deve ser síncrono dentro da transação.

## 5. Estrutura Modular Proposta (Fase 1)

```
app/
  Actions/                      # (opcional) actions globais de infraestrutura
  Domain/
    Authentication/             # Login, sessões, recuperação (em geral via Breeze/Fortify)
      Actions/ Services/ Policies/
    Users/                      # CRUD usuários, ativação/desativação, último acesso
      Actions/ DTOs/ Policies/
    Clients/                    # Cadastro, status, página 360°
    Commercial/                 # Leads, oportunidades, propostas
    Demands/
      Actions/ConvertDemandToProjectAction.php   # caso crítico transacional
      Actions/ApproveDemandAction.php
      Actions/RequestComplementAction.php
      States/                   # Enums: DemandStatus, ProjectStatus, TaskStatus
    Projects/
      Actions/ Queries/
    Tasks/
    Briefings/                  # Document types + items por categoria
    Templates/                  # Project templates + tasks; Websites templates (futuro)
    Websites/                   # Inventário, domínios, deployments, manutenção
    Finance/                    # Transações, categorias, pagamentos, despesas
    Notifications/              # Centro de notificações + dispatch
    Settings/                   # Configurações da plataforma
    Reports/                    # Queries agregadas (dashboard/relatórios)
  Http/
    Controllers/
      DashboardController.php  # Inertia, dados agregados
      ClientController.php
      DemandController.php
      ProjectController.php
      TaskController.php
      ...
    Requests/                   # FormRequests validados (pt-BR messages)
    Middleware/                 # Auth, Verified, Admin, Logs de atividade
    Resources/                  # Para exports JSON quando necessário
  Models/
    User.php Client.php Demand.php Project.php Task.php ...
  Notifications/ Events/ Listeners/ Jobs/ Mail/
  Policies/                     # ou por domínio
  Observers/
routes/
  web.php                       # Inertia (principal)
  api.php                       # SOMENTE webhooks/integrações externas futuras (MVP mínimo)
resources/js/
  Pages/
    Auth/{Login,ForgotPassword,ResetPassword}.tsx
    Dashboard.tsx
    Clients/{Index,Show,Create,Edit}.tsx
    Commercial/{Index,Show}.tsx
    Demands/{Index,Create,Edit,Show}.tsx
    Projects/{Index,Show,Create,Edit}.tsx   # Show com abas: Visão geral, Briefing, Tarefas, Arquivos, Atividades, Técnica, Financeiro
    Tasks/{Index,Show}.tsx
    Templates/{Index,Create,Edit}.tsx
    Websites/{Index,Show}.tsx
    Finance/{Index,Show}.tsx
    Reports/{Index}.tsx
    Team/{Index}.tsx            # Users
    Settings/{Index}.tsx
  Layouts/
    AuthenticatedLayout.tsx     # Menu lateral fixo (Doc.md §8.2), sidebar colapsável, topbar
    GuestLayout.tsx
  Components/
    ui/                         # wrapper da biblioteca de componentes [pendente]
    Tables/ Forms/ Cards/ Charts/ Feedback/
  Hooks/ lib/
    usePageProps.ts formatDateBR.ts formatBRL.ts
  app.tsx                       # createInertiaApp (Inertia v3)
  types/                        # tipos gerados (PageProps, Demand, Project...)
database/
  migrations/ seeders/ factories/
tests/
  Feature/ Unit/
config/                         # auth, filesystems (s3), queue, cache
```

Frontend: Inertia + React 19 + TypeScript estrito, layout administrativo, menu lateral fixo (12 itens, filtrado por permissão), cards/tabelas/filtros/busca global, feedback visual, responsivo. Datas `dd/mm/yyyy`, moeda `BRL` via `Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'})`.

## 6. Backend Guidelines

- Validar sempre no backend (FormRequests). Autorizar sempre no backend (Policies/Gates). Nunca confiar em `authorizeResource` apenas no frontend.
- Controllers finos; Actions/Services contêm regras. Exemplo: conversão, aprovação, geração de tarefas, cálculo financeiro (nunca confundir contratado com recebido).
- Enums PHP para status/prioridades (Demands: `DemandStatus`; Projects: `ProjectStatus`; Tasks: `TaskStatus`, `TaskPriority`; Clients: `ClientStatus`; Websites: `WebsiteStatus`; Commercial: `LeadStage`). Cast automático no Eloquent.
- Soft deletes onde aplicável (clientes, demandas, projetos, tarefas); **nunca** hard delete em `financial_transactions`, `payment_records`, `activity_logs`.
- Auditoria: `activity_logs` + `demand_status_history` + `project_activities` para ações importantes.
- Uploads: validar extensão/MIME/tamanho no FormRequest, armazenar em disco `s3`, metadados em `uploaded_files` / `demand_attachments` / `task_attachments`, URLs privadas assinadas para confidenciais.
- Cache, filas e jobs: notificações automáticas (nova demanda, aprovação, tarefa atribuída, prazo próximo) via `Notification` + `ShouldQueue`.

## 7. Infraestrutura (resumo — detalhe em OPERATIONS.md)

Ambientes: `dev`, `staging` (homologação), `production`. Heroku-like: web (PHP-FPM), worker (queue), scheduler (cron/Artisan `schedule:run`), PostgreSQL gerenciado, storage S3-compatível, HTTPS (Let's Encrypt / provider), domínio próprio, backups diários de banco e arquivos, logs centralizados. Deploy via GitHub → CI (composer install, migrate, build Vite, testes) → deploy automático após aprovação. Branch `main` protegida; branches por funcionalidade. Rollback documentado.

## 8. Decisões Pendentes Desta Área

- Biblioteca de componentes React (shadcn/ui, Mantine, Chakra, MUI, Ant Design...).
- Tailwind v3 vs v4.
- Breeze vs Fortify vs autenticação manual.
- Spatie Permission vs policies nativas.
- UUID v7 vs bigint auto-increment.
- Queue/cache driver (database vs Redis).
- Docker Sail/Herd vs ambiente nativo.
- Hospedagem final (Render vs alternativa).
- Estratégia SSR (desabilitar no MVP).
- Biblioteca de gráficos (Recharts, Chart.js, ECharts...).
- Form library (Inertia `useForm` nativa vs React Hook Form + Zod).
- Pacote de auditoria (Spatie Activity Log vs próprio).

Todas são registradas em `DECISIONS.md` e não devem ser assumidas sem aprovação.
