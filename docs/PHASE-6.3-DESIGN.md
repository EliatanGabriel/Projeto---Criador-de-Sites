# PHASE 6.3 — Modelagem e Arquitetura dos Indicadores Operacionais e Alertas

**Versão:** 6.3.1  
**Status:** Design (sem implementação)  
**Baixa:** 2026-11-07  
**Fonte:** Doc.md §5 §§10/12, PHASE-6-DESIGN.md, DATABASE/SECURITY/ARCHITECTURE

> **Escopo desta entrega (6.3.1):** somente análise, fechamento de regras de negócio e documentação da arquitetura. Nenhuma migration, Model, Controller, Job ou Job Scheduler foi criado ou alterado. A implementação será a Fase 6.3.2 após aprovação explícita.

---

## 1. Diagnóstico da arquitetura existente

### 1.1 Base verificada (Fases 1–6.2 + auditorias)

| Dimensão | Estado hoje |
|---|---|
| **Site** | `Website` (uuid+SoftDeletes, `status desenvolvimento/publicado/suspenso/manutencao/desativado`, `technologies jsonb`, `technical_owner`) |
| **Projetos vinculados** | `website_projects` N—N com `relation_type origin/rework/maintenance/evolution` (pivô fonte única), `projects.demand_id UNIQUE` |
| **Domínios** | `WebsiteDomain` (`domain lower unique`, `expires_at date`, `is_primary` parcial único) |
| **Hospedagens** | `Hosting` independente e reutilizável (`provider/name/plan/account_identifier/status ativo/suspenso/cancelado`), sem `next_renewal_at` |
| **Ambientes** | `WebsiteEnvironment` (`website_id cascade`, `hosting_id nullable`, `type development/staging/production`, `url`, `status ativo/suspenso/offline/desativado`), parcial único produção ativa — sem `ssl_expires_at` |
| **Repositórios** | `WebsiteRepository` (`website_id`, `environment_id nullable`, url única) |
| **Publicações** | `WebsitePublication` imutável manual (`environment restrict`, `version`, `published_at`, `result sucesso/falha/rollback`) |
| **Manutenção** | `MaintenanceRecord` (project/task nullable, type 6, status 4); `website.status=manutencao` denormalizada |
| **Credenciais** | `Credential` (encrypted cast, 1-of-4 CHECK PG + triggers SQLite, `hidden`), `CredentialAccessLog` imutável |
| **Progresso projeto** | `Project::progressData()` por projeto isolado (não agregável a site hoje) |
| **Tarefas** | `Task` (`status todo/in_progress/in_review/blocked/done/cancelled`, `deleted_at via SoftDeletes`, `phase_name/order`) |
| **Notificações** | `notifications` DB, `unreadCount` no header, `Notification` por evento — sem job agendado hoje |
| **Fila/Scheduler** | `QUEUE_CONNECTION=database` (jobs table), `Cache=database`, `routes/console.php` apenas `inspire` — **nenhum Scheduler/Jobs de negócio** |
| **Permissões** | `websites.view/manage/publications/maintenance` + `hostings.view/manage` + `credentials.view` (OWNER) |
| **Policies** | `WebsitePolicy::view` (OWNER todos; COMMERCIAL escopo oportunidade/demanda; DEVELOPER escopo projeto `technical_owner/member/tasks`) |

### 1.2 Lacunas identificadas para 6.3

- Progresso hoje calculado por projeto; agregação cross-projetos com dedup/exclusão ainda inexistente.
- `hostings.next_renewal_at` e `website_environments.ssl_expires_at` **não existem** (precisam ser criados).
- Nenhuma tabela de alertas/histórico de alertas; vencimentos hoje apenas exibidos (expiração de domínio).
- Nenhum Job/Schedule configurado — insights operacionais manuais.

### 1.3 Convenções herdadas mantidas

- UUID v7 + SoftDeletes em entidades de negócio; histórico/auditoria **sem** SoftDeletes.
- `varchar` + validação PHP (não PG ENUM); `jsonb` apenas para lista de tecnologias.
- Policies como única fonte de escopo; Actions para cálculo/transição transacional.
- `database` queue (sem Redis/KMS/integrações externas).

---

## 2. Regras de cálculo de progresso

### 2.1 Fórmula (proposta aprovada para implementação)

Seja `E` o conjunto de tarefas elegíveis do site:

| Regra | Definição |
|---|---|
| **Universo** | Todas as `tasks` cujos `projects` estejam vinculados via `website_projects` (todos os tipos: `origin`, `rework`, `maintenance`, `evolution`). |
| **Exclusões** | `tasks.status = 'cancelled'`; `projects.status = 'cancelled'`; `tasks.deleted_at IS NOT NULL`; `projects.deleted_at IS NOT NULL`. |
| **Projetos concluídos** | Incluídos (`tasks` contam — histórico preservado). |
| **Concluídas** | `tasks.status = 'done'` → numerador. |
| **Denominador** | `tasks.status IN ('todo','in_progress','in_review','blocked')` + `tasks.status = 'done'` (após exclusões). |
| **Estado vazio** | Se `|E| = 0` → retorno `has_tasks: false`, `percentage: null`, `state: "sem_tarefas"` (não `0%`). |
| **Fórmula** | `progresso = done / eligíveis × 100` (arredondado inteiro, clamp 0..100) |

**Observações:**

- Transferência de tarefa entre projetos (mudança de `project_id`) não pode ser feita via UI sem regra explícita — se houver, o cálculo deve ser **recomputado por `project_id` atual** (histórico por `task_histories.metadata`).
- Reabertura (`done → in_progress`) via `update` reduz `done` automaticamente (sem cache).
- Soft-delete de tarefa remove do denominador/numerador; hard-delete proibido (SoftDeletes já impõe).
- `blocked` conta no denominador (trabalho pendente), nunca no numerador.
- **Não armazenar percentual** no banco (poderia divergir). Cálculo on-demand via Action/Query Object.

### 2.2 Local de cálculo

- Proposto `app/Domain/Websites/Actions/CalculateWebsiteProgressAction` (ou Query Object `WebsiteProgressQuery`) retornando `has_tasks, done, total, percentage, by_status`.
- `Website::progressData()` (a criar) delega à Action formando a agregação `website_projects → projects → tasks` com exclusões acima — sem copiar `Project::progressData()` por projeto.
- Cache de 5 minutos via `Cache::remember` opcional (chave `website_progress:{id}`) — invalidação por `TaskObserver`/`ProjectObserver` futuros.

---

## 3. Alterações necessárias no banco

| Tabela | Coluna proposta | Tipo / notas |
|---|---|---|
| `hostings` | `next_renewal_at` | `date nullable` + `index` |
| `website_environments` | `ssl_expires_at` | `date nullable` + `index` |
| `hostings` | `notifications_from` | `date nullable` (quando um cliente externo é notificado — fora de escopo desta fase) |
| `website_domains.expires_at` | já existe (`date nullable`) — **sem alteração** | |

- Tipo `date` (não `datetime`) para vencimentos de ciclo diário; timezone da aplicação `APP_TIMEZONE=America/Sao_Paulo`, comparação por `today()` Carbon em PT-BR.
- `null` = data desconhecida (sem alerta).

---

## 4. Modelagem dos alertas — `operational_alerts`

> **Proposta de entidade de status agregado (não duplicação de activos):** alertas referenciam exatamente um ativo subjacente quando aplicável, mas são registros de ocorrência operacional próprios (imutável até resolução).

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites cascade index` | Obrigatório — alerta sempre pertence a um site |
| `alert_type` | enum `dominio_vencendo/ssl_vencendo/hosting_vencendo` `index` | |
| `severity` | enum `info/warning/critical` `index` | `info ≥8d`, `warning 7d/15d`, `critical 1d/vencido` (ver §6) |
| `source_type` | string nullable | `WebsiteDomain`, `Hosting`, `WebsiteEnvironment` |
| `source_id` | uuid nullable `index` | `source_type+source_id` = origem (polimórfico, sem FK dura) |
| `title` | string | Gerado automaticamente a partir do ativo |
| `description` | text nullable | |
| `due_at` | date `index` | Data de vencimento do ativo (fotografia, não referência viva) |
| `fingerprint` | string `index` | `hash(alert_type + '|' + source_type + '|' + source_id + '|' + due_at)` |
| `assigned_to` | uuid FK `users nullable nullOnDelete index` | |
| `read_at` | timestamp nullable | |
| `resolved_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamp | `timestamps` (imutável exceto leitura/resolução) |

`softDeletes` **não** — histórico por `resolved_at` + `activities`. Índice composto:

- `UNIQUE (fingerprint) WHERE resolved_at IS NULL` (PG/SQLite parcial via `DB::statement`, igual às unicidades existentes) — garante idempotência por ativo+vencimento **aberto**.
- `INDEX (website_id, due_at)`, `INDEX (assigned_to, resolved_at NULL)`.

---

## 5. Histórico de tratamento — `operational_alert_activities`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `alert_id` | uuid FK `operational_alerts cascade index` | |
| `user_id` | uuid FK `users nullable nullOnDelete index` | `null` = sistema (auto_resolved) |
| `event` | enum `created/read/assigned/resolved/reopened/auto_resolved` | |
| `description` | text nullable | Sem credenciais/segredos |
| `metadata` | jsonb nullable | `from/to` quando aplicável |
| `created_at` | timestamp `index` | Apenas `created_at` (append-only) |

`public $timestamps = false` + `created_at` manual, igual a `TaskHistory`. Sem update/delete via UI; sem SoftDeletes.

---

## 6. Regras de vencimento

### 6.1 Estratégia escolhida: **uma ocorrência por ativo + vencimento, com elevação de severidade**

- Fingerprint único garante **uma alerta aberto** por `(tipo, fonte, due_at)`.
- Se `due_at` muda (ex.: renovação), o alerta antigo é **auto-resolvido** (`auto_resolved`) e novo fingerprint gerado na próxima execução — sem duplicação.
- Severidade é **atualizada in-place** (não nova ocorrência) quando o ativo entra em faixa mais crítica (ex.: `info→critical`), preservando leitura/resolução.

| Faixa | Severidade | Mensagem |
|---|---|---|
| ≤30d e >15d | `info` | "Vence em ~30 dias" |
| ≤15d e >7d | `warning` | "Vence em ~15 dias" |
| ≤7d e >1d | `critical` | "Vence em 7 dias" |
| ≤1d | `critical` | "Vence amanhã" |
| < today | `critical` | "Vencido em DD/MM/AAAA" |

### 6.2 Comportamentos de borda

| Situação | Comportamento |
|---|---|
| **Data muda (renovação)** | Novo `due_at` → nova fingerprint; próxima varredura cria alerta novo; antigo auto-resolve |
| **Ativo renovado (nova data futura)** | Idem — antigo auto-resolve, sem reabertura |
| **Ativo deletado (SoftDeletes)** | Varredura ignora (`withTrashed` não) — alerta aberto permanece mas **não** gera novos; ao restaurarmos, pode gerar de novo |
| **Data vira null** | Varredura ignora; alerta aberto auto-resolve na próxima execução |
| **Alerta resolvido** | Mantido `resolved_at` + atividade `resolved`; nova varredura não recria enquanto `due_at` não muda |
| **Reabertura** | Manual `reopened` (limpa `resolved_at`) ou sistema se `due_at` muda e ativo não vencido |

---

## 7. Jobs e Scheduler

- **Job:** `app/Jobs/CheckExpirationsJob` (`ShouldQueue`, `WithoutOverlapping`, `tries=3`, `timeout=120`) → invoca `App\Domain	Websites\Actions\CheckExpirationsAction`.
- **Action:** varre domínios (exceto SoftDeleted), ambientes (`ssl_expires_at`), hospedagens com ambientes em uso (`next_renewal_at`): para cada ativo não-nulo, calcula severidade, `firstOrCreate` por fingerprint aberto, atualiza severidade se crítica nova, resolve os que não mais batem (auto-resolve).
- **Scheduler:** `routes/console.php` → `Schedule::job(new CheckExpirationsJob)->dailyAt('07:00')->timezone('America/Sao_Paulo')->withoutOverlapping()->runInBackground()`.
- **Idempotência:** fingerprint unique + `updateOrCreate` por `fingerprint` aberto; `WithoutOverlapping` impede duas execuções simultâneas.
- **Logs:** sem segredos; apenas `info` com contagem de varreduras (não credenciais).
- **Testabilidade:** `Carbon::setTestNow()`, jobs síncronos (`queueDriver=sync` em testes).

---

## 8. Indicadores e consultas

| Indicador | Definição (status Website existente) |
|---|---|
| Total de sites | `Website::count()` |
| Sites ativos | `status IN ('publicado')` |
| Em desenvolvimento | `status = desenvolvimento` |
| Pausados | `status = suspenso` |
| Em manutenção | `status = manutencao` |
| Com vencimento próximo | `EXISTS operational_alerts WHERE resolved_at IS NULL AND severity != info` |
| Com ativo vencido | `EXISTS operational_alerts WHERE due_at < today AND resolved_at IS NULL` |
| Média de progresso | `AVG(progress.percentage)` apenas sites com `has_tasks=true` |

**Consultas:** agregações `COUNT`, `AVG`, `SUM` via Eloquent builder, sem stored procedure. Escopo aplicado antes da agregação (ver §9).

---

## 9. Permissões e segurança

Novas permissões a criar em `RolePermissionSeeder`:

- `alerts.view` (consultar indicadores/alertas)
- `alerts.read` (marcar como lido/atribuir)
- `alerts.resolve` (resolver/reabrir)

Matriz proposta:

| Permissão | OWNER | COMMERCIAL | DEVELOPER |
|---|---|---|---|
| `alerts.view` | ✅ (todos) | ✅ escopo do site | ✅ escopo do projeto |
| `alerts.read` | ✅ | ✅ escopo | ✅ escopo |
| `alerts.resolve` | ✅ | ❌ (só leitura/assign) | ✅ escopo |

**Isolamento UUID:** toda rota de alerta filtra `website_id` via `WebsitePolicy::view` (reusa oportunidades/demandas para COMMERCIAL e `projects` para DEVELOPER). Marcação e atribuição exigem objeto acessível; otherwise 403. Credenciais permanecem `credentials.view` OWNER-only sem interseção.

---

## 10. Interfaces

- **Dashboard de indicadores:** 8 cards (I1–I8 acima) com drill-down para alertas filtrados.
- **Detalhe do site:** card de progresso (has_tasks/percentage/byStatus) + card "Próximos vencimentos" (alertas abertos do site).
- **Central de alertas** (`/alerts`): lista paginada com filtros `site/type/severity/assigned_to/due_at`, badges, contador não-lidas no header (sino com `alerts.view`), detalhe com histórico, ações Modificar responsável / Marcar lida / Resolver / Reabrir (com permissão).
- Componentes reutilizáveis: `components/ui/alert`, `badge`, filtros — identidade visual shadcn/ui preservada.

---

## 11. Estratégia de testes

- **Progresso:** sem tarefas → `sem_tarefas`; 50% (1 done / 2 total), dedup de tarefa transferida, exclusões (cancelled, soft-deleted, projeto cancelado).
- **Alertas idempotência:** varredura dupla não duplica (fingerprint único).
- **Regras de vencimento:** 30/15/7/1/vencido com `Carbon::setTestNow()`.
- **Job + Scheduler:** `CheckExpirationsAction` direto + `php artisan schedule:list` contém job diária.
- **Permissões:** COMMERCIAL/DEVELOPER escopados; sem escopo → 403.
- **PG17 concorrência:** dois jobs simultâneos resolvem em 1 alerta (única fingerprint com `NOWAIT` ou `23505→` no-op).
- **SQLite/PG17:** mesma suíte Pest (uso de `withoutOverlapping` testável sem Redis).

---

## 12. Riscos técnicos

| # | Risco | Impacto | Mitigação |
|---|---|---|---|
| 1 | Fingerprint colide em domínios derivados (`www` vs `apex`) | Médio | Normalização `lower/trim` já existente; fingerprint inclui `domain` normalizado |
| 2 | `withoutOverlapping` com `database` queue sem `cache` Redis | Médio | `Cache::store('database')` + lock `WithoutOverlapping` (testado em PG) |
| 3 | Progresso sem tarefas lido como 0% | Médio | Estado explícito `sem_tarefas` + `percentage: null` |
| 4 | Alerta reaberto em loop por `due_at` oscilante | Baixo | Só reabre quando fingerprint muda; resolução manual permanece |
| 5 | Escopo de consulta vaza site de outro cliente | Alto | Filtro `website_id` via `WebsitePolicy::view` em todas as queries (teste por perfil) |
| 6 | Job varre SoftDeleted e cria alertas fantasma | Médio | Queries excluem SoftDeleted explicitamente |

---

## 13. Decisões pendentes (requer aprovação antes da 6.3.2)

| ID | Tema | Opções | Recomendação |
|---|---|---|---|
| PD-631 | Severidade vs. ocorrências por faixa | (A) uma ocorrência com elevação; (B) N ocorrências | **(A)** fingerprint único + elevação |
| PD-632 | Progresso em cache | (A) on-demand; (B) cache 5min | **(A)** nesta fase |
| PD-633 | Breakpoint do dashboard | (A) 8 cards; (B) seção compacta | **(A)** 8 cards I1–I8 |
| PD-634 | Job horário | (A) diária 07:00; (B) horária | **(A)** diária |
| PD-635 | `next_renewal_at` renomeado para `expires_at`? | (A) manter `next_renewal_at`; (B) padronizar `expires_at` | **(A)** `next_renewal_at` (integração com domínio) |

---

## 14. Critérios de aceite (6.3.2)

- Progresso calculado corretamente (incl. sem tarefas, dedup, exclusões).
- Alertas idempotentes por fingerprint (uma aberto por ativo+vencimento).
- Job diário idempotente + `withoutOverlapping` + logs sem segredo.
- Permissões escopadas (owner/commercial/developer) sem vazamento.
- `php artisan test` (SQLite+PG17), `typecheck`, `build` verdes.

---

## 15. Sequência recomendada de implementação

1. **Migrations + Models + Enums + Policy** (alerts, activities, `next_renewal_at`, `ssl_expires_at`) — sem UI.
2. **Progresso** (`WebsiteProgressAction`) + testes de cálculo.
3. **Job/Action** de vencimentos + `Schedule` + testes de idempotência.
4. **Controllers/Resources** de alertas/indicadores (dashboard + site) + Policies.
5. **Frontend** (dashboard cards, detalhe do site, central `/alerts`, cabeçalho com contador).
6. **Docs** (`DATABASE/SECURITY/DECISIONS`) + validação SQLite/PG17/typecheck/build.

> **Não implementar 6.3.2 sem aprovação explícita.**
