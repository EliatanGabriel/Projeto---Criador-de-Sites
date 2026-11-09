# DECISIONS — WebStudio OS

**Versão:** 2.3 — Migração Postgres/Supabase (atualizado)
**Propósito:** registrar alterações importantes de escopo, arquitetura e tecnologia (Doc.md §11.2).

## Decisões Aprovadas

| ID | Decisão | Justificativa / Fonte | Status |
|---|---|---|---|
| D-001 | Monólito modular, sem microsserviços no MVP | Doc.md §4.1/§4.2 | Aprovada |
| D-002 | Stack: Laravel 13 + React 19 + Inertia.js v3 + TypeScript ^5.9 + PostgreSQL 17 + Tailwind CSS v4 | Doc.md §4.1 + P-002 aprovada Fase 1 | Aprovada |
| D-003 | Sem API REST separada para todas as operações no MVP (Inertia primeiro) | Doc.md §4.1 | Aprovada |
| D-004 | Domínios: Authentication, Users, Clients, Commercial, Demands, Projects, Tasks, Briefings, Templates, Websites, Finance, Notifications, Settings, Reports | Doc.md §4.2 | Aprovada |
| D-005 | Regra central: nenhuma demanda entra em desenvolvimento sem análise técnica | Doc.md §3 | Aprovada |
| D-006 | Conversão demanda→projeto transacional (falha não deixa projeto parcial) | Doc.md §5 Módulo 09 | Aprovada |
| D-007 | Uma demanda convertida não pode gerar outro projeto por acidente | Doc.md §5 Módulo 05 | Aprovada |
| D-008 | Financeiro: nunca confundir contratado com recebido | Doc.md §5 Módulo 11 | Aprovada |
| D-009 | Perfis: OWNER, COMMERCIAL, DEVELOPER via Spatie Permission, validados no backend | Doc.md §7.1 + P-004 aprovada Fase 1 | Aprovada |
| D-010 | Sem registro público; acesso apenas equipe autorizada | Doc.md §1.3 | Aprovada |
| D-011 | Arquivos em armazenamento compatível com S3 (disco `s3`) | Doc.md §4.1/§7.2 | Aprovada |
| D-012 | Hospedagem cloud; deploy inicial Render ou equivalente; branch `main` protegida | Doc.md §9 | Aprovada (hospedagem final pendente P-008) |
| D-013 | Desenvolvimento assistido por OpenCode | Doc.md §11 | Aprovada |
| D-014 | MVP: organizar a operação; não gerar sites automaticamente | Doc.md §13 | Aprovada |
| D-015 | pt-BR, datas BR, moeda BRL, interface responsiva | Doc.md §8 | Aprovada |
| D-016 | Componentes React via shadcn/ui (style new-york) | Fase 1 aprovada (P-001) | Aprovada |
| D-017 | Tailwind CSS v4 (via @tailwindcss/vite) | Fase 1 aprovada (P-002) | Aprovada |
| D-018 | Autenticação via Laravel Fortify (login, logout, recuperação, sem registro público, sem 2FA/passkeys na Fase 1) | Fase 1 aprovada (P-003) | Aprovada |
| D-019 | Autorização via Spatie Laravel Permission 7 | Fase 1 aprovada (P-004) | Aprovada |
| D-020 | IDs primários UUID v7 (`Str::uuid7()`, `$table->uuid()`) | Fase 1 aprovada (P-005) | Aprovada |
| D-021 | Filas/cache via `database` driver no MVP | Fase 1 (P-006 parcial) | Aprovada |
| D-022 | Ambiente local via Docker Compose para PostgreSQL 17 + PHP nativo (porta compose 5432) | Fase 1 (P-007) | Aprovada |
| D-023 | Testes backend via Pest 4 + PHPUnit 12 | Fase 1 aprovada (P-012) | Aprovada |
| D-024 | Postgres 17-alpine via compose; SSR do Inertia desabilitado | Fase 1 | Aprovada |
| D-025 | Fase 2: users (is_active, último OWNER protegido, toggle-active), clients (pf/pj, doc opcional, softDeletes), opportunities (7 status, sem conversão automática) | Prompt Fase 2 | Aprovada |
| D-026 | Policies + FormRequests + middleware can: — sidebar filtra por permissions compartilhadas | Prompt Fase 2 §5 | Aprovada |
| D-027 | Fase 3: demands (draft/submitted/under_review/needs_information/approved/rejected/cancelled via Action transacional, sem conversão auto), briefings 1-1 colunas, anexos local privado 10MB whitelist, histories imutáveis, oportunidade valida cliente | Prompt Fase 3 | Aprovada |
| D-028 | Fase 4: ConvertDemandToProjectAction transacional (lockForUpdate + UNIQUE projects.demand_id + rollback, sem duplicidade), projects/tasks/histories/comments, notificações database (sem e-mail), projeções de progresso | Prompt Fase 4 | Aprovada |
| D-029 | Fase 5: templates com fases/tarefas ordenadas, snapshot jsonb + phase_name/order nas tasks (independência do template), template opcional na conversão, sem hard delete | Prompt Fase 5 | Aprovada |
| D-030 | Fase 6.2: websites + website_projects N:N (origem única, pivô fonte única), hostings independentes, domains/hosting/env/repos separados, publications append-only, maintenance via projects/tasks, credentials encrypted + audit | Prompt Fase 6.2 | Aprovada |
| D-031 (PD-701) | Fase 7: push/release são eventos de integração; deployment é início de implantação; deployment_status é o resultado; website_publications representa apenas implantações reais; `pending` só para implantação efetivamente iniciada; eventos rastreáveis e idempotentes | Prompt Fase 7.1 | Aprovada |
| D-032 (PD-702) | Fase 7: GitHub com Personal Access Token de escopo mínimo por repositório, armazenado em `credentials` (encrypted cast, OWNER-only), sem exposição em Inertia/HTTP/logs; migração futura para GitHub App preservada | Prompt Fase 7.1 | Aprovada |
| D-033 (PD-703) | Fase 7: sem deploy automático via API da hospedagem nesta fase (provedor P-008 indefinido); design deve permitir evolução sem assumir provedor | Prompt Fase 7.1 | Aprovada |
| D-034 (PD-704) | Fase 7: health-check diário 06:30 `America/Sao_Paulo`, timeout 10 s, verificação HTTP/HTTPS + validade SSL, registro de status HTTP/duração/data, ignora desativados e ambientes sem domínio válido, proteção SSRF (bloqueio localhost/privados/link-local/internos + redirect seguro), sem corpo sensível | Prompt Fase 7.1 | Aprovada |
| D-035 | Fase 7.2: sync GitHub **SÍNCRONO** via `SyncWebsiteRepositoryAction` no request manual do OWNER (sem Job/fila); colunas do banco são o cache (página nunca chama o GitHub); `lockForUpdate` serializa syncs; PRs só contagem (per_page 100); mesma Action será reusada por Job agendado em fase futura | Prompt Fase 7.2 | Aprovada |
| D-036 | Fase 7.3: webhook `POST /webhooks/github` fora do `auth`, CSRF exceto + `throttle:60,1`, limite 1 MB, HMAC `sha256=` com `hash_equals`, só grava após HMAC válido; `delivery_id unique` (insert-first + recuperação `23505` fora da transação); eventos inválidos 401/404/400 sem registro; `orphaned_status` para status sem deployment (sem `pending` inventado) | Prompt Fase 7.3 | Aprovada |
| D-037 | Fase 7.4: SSRF via cURL hostname + `CURLOPT_RESOLVE hostname:port:ip` (pin) + `CURLINFO_PRIMARY_IP` igual ao validado; manual só registra (sem estado/alerta); `scheduled_run_id` estável por execução lógica (payload, retries); ciclos UUID; retenção 365 dias | Prompt Fase 7.4 | Aprovada |
| D-038 | Fase 7.4-correção: identidade persistente `site_health_runs` por domínio/slot (coordenador recupera/cria e despacha 1 Job/domínio com runId no payload); runId exclusivo por domínio (sequência global monotônica); `UNIQUE(scheduled_run_id)` mantida sem compartilhamento | Prompt 7.4 correção | Aprovada |
| D-040 | Fase 8: financeiro com categorias+lancamentos, pagamentos parcelados transacionais (lockForUpdate, nunca exceder total), cancelamento por status, DECIMAL(12,2) BRL, relatórios mensais, finance.view/manage OWNER-only | Prompt Fase 8 | Aprovada |
| D-041 | Simplificacao pos-deploy: 100% SQLite (substitui PostgreSQL/Supabase aprovados anteriormente); 1 servico no Render, fila+scheduler no mesmo container via supervisord, migrate automatico no entrypoint; sem persistencia entre redeploys sem Persistent Disk | Pedido do usuario em 2026-11-08 | Aprovada |
| D-039 | Fase 7.4-reconciliação: janela móvel 7 dias (America/Sao_Paulo); `pending` commitada antes do dispatch; `dispatched` abandonado (>10 min) reenfileirado com mesmo runId; triagem de elegibilidade histórica (created_at/deleted_at ≤ slot); 1 domínio não bloqueia os demais | Prompt 7.4 reconciliação | Aprovada |
| D-042 | Painéis separados mas conectados: rotas `/admin`, `/commercial`, `/developer` com dashboards/queries próprias e roles estritas; módulos compartilhados (clients/demands/projects/tasks/notifications) com escopo por vínculo (comercial: demandas/projetos próprios; developer: projetos/tarefas atribuídos, briefing vinculado sem `negotiated_value`/`internal_notes`); `authorize('view'/'update')` por ID em Project/Task/Demand (show/edit/comment/store); `PATCH projects/{project}/status` com `can:projects.view`; `/dashboard` genérico redireciona DEVELOPER puro para `/developer`; conexão via Demand→Project transacional + notificações cruzadas | Pedido de certificação em 2026-11-08 + Doc.md §3/§7 | Aprovada |
| D-043 | Migração SQLite→PostgreSQL/Supabase para persistência real: `DB_CONNECTION=pgsql` + `DB_URL` (connection string Supabase, `sync:false` no Render, nunca versionar); Dockerfile com `postgresql-dev` + `pdo_pgsql/pgsql`; `docker-compose.yml` com `postgres:17-alpine` local; `render.yaml` com Persistent Disk só para uploads (`/app/storage/app/private`); testes mantêm SQLite `:memory:` (PG validado com migrations+seed em Postgres 17 local) | Perda de dados por redeploy em SQLite efêmero + pedido do usuário em 2026-11-08 | Aprovada |
| D-044 | Relatório mensal financeiro (`/finance/report`) liberado para COMMERCIAL via Gate `view-reports` (`reports.view` OU `finance.view`); demais rotas financeiras seguem `finance.view/manage` OWNER-only (D-040 preservada). Exceção aprovada pelo usuário em 2026-11-08 ao corrigir 403 no painel Comercial (antes sidebar exibia `reports.view` mas rota exigia só `finance.view`). Testes: `FinancialTest` (COMMERCIAL OK, DEVELOPER 403) | Pedido do usuário em 2026-11-08 | Aprovada |
| D-045 | Página compartilhada `/profile` (leitura) para qualquer usuário autenticado (OWNER/COMMERCIAL/DEVELOPER): nome, e-mail, status, último login, perfis, painéis e permissões do próprio logado; sem edição/alteração de dados. Acesso via menu lateral e avatar do header (layout compartilhado vale para todos os painéis) | Pedido do usuário em 2026-11-08 | Aprovada |
| D-046 | Performance: agregações em SQL (`SUM(CASE)`/`GROUP BY`) em `/finance/report`, dashboards (Admin/Comercial/Developer) e `/alerts` (1 query vs 4-8 counts); removal de props não utilizadas (`transactions` em report, `categories/clients/projects` em `/finance`) e eager `opportunity` não usada no Comercial — sem mudança de regras de negócio | Demora relatada ao abrir Relatórios em 2026-11-08 | Aprovada |
| D-047 | Performance produção (Render): OPcache (`validate_timestamps=0`), `php artisan optimize` no entrypoint, nginx `gzip` + `Cache-Control: immutable` em `/build/`, cache `file` (Spatie/permissão local reduz latência Render→Supabase) e `prefetch` hover nos links da sidebar/perfil — sem mudar regras de negócio. Limite restante: spin-down do plano free do Render (20-50s na 1ª carga após 15min parado) exige plano pago para eliminar | Demora relatada na navegação em produção em 2026-11-08 | Aprovada |
| D-048 | `webstudio:ensure-admin` e `webstudio:create-user` passam a garantir o perfil mesmo quando o usuário já existe (`syncRoles` + `is_active=true`), permitindo corrigir 403 em produção sem apagar/recriar o usuário | 403 em `/developer` com Eliatan/Roger em produção em 2026-11-08 | Aprovada |
| D-049 | Layout persistente Inertia (`app.tsx` registra `layout`), `memo` em Sidebar/Header, remoção de props/eager mortos (todas as páginas sem layout local); `SESSION_DRIVER=cookie` em produção e `PDO::ATTR_PERSISTENT` no Postgres para reduzir roundtrips remotos Render→Supabase | Investigação profunda de lentidão em 2026-11-08 (prod ~2.8s vs local ~0.06s) | Aprovada |

## Decisões Pendentes

| ID | Tema | Opções | Recomendação | Bloqueia |
|---|---|---|---|---|
| P-008 | Hospedagem final | Render vs Fly.io vs Hetzner | Render se suportar PHP 8.4 + PG + filas | Fase 1 pós-fundação |
| P-009 | Biblioteca de gráficos | Recharts vs Chart.js vs ECharts | Recharts | Fase 3 |
| P-010 | Formulários FE | useForm nativo vs RHF+Zod | useForm nativo inicial | Fase 4 |
| P-011 | Testes FE | Vitest vs apenas typecheck | Vitest apenas fluxos críticos | Fase 2+ |
| P-013 | Política de senha e 2FA | tamanho, complexidade, 2FA OWNER? | Mínimo 8 chars, 2FA opcional | Fase 2 |
| P-014 | Exportação CSV/PDF | league/csv vs dompdf vs excel | league/csv + Dompdf | Fase 8 |
| P-015 | Monitoramento de erros | Sentry vs Flare vs logs | Sentry/Flare se custo ok | Pós-Fase 1 |
| P-016 | Fornecedor S3-compatível | AWS S3 vs R2 vs B2 | R2 ou B2 | Fase 4 |

## Histórico de Alterações

- **2026-11-07 (Fase 0):** Criado este arquivo com decisões aprovadas (D-001 a D-015 do Doc.md v1.0) e pendências (P-001 a P-016) para deliberação na Fase 1.
- **2026-11-07 (Fase 1):** P-001 a P-007, P-012 aprovadas e implementadas. Versões validadas: Laravel 13.40, PHP 8.5 (hospedagem exige 8.3–8.5), Inertia 3.11/3.7, React 19.3, TS 5.9, Tailwind 4, shadcn/ui new-york, Fortify 1.41, Spatie 7.4, Pest 4.7, PG 17, Docker Compose. SSR desabilitado. 2FA/passkeys removidos dos features Fortify na Fase 1 (infra não exigida); reativar após definição P-013. Ajuste: `config/permission.php` model_id alterado para UUID (Spatie). Ajuste: Inertia middleware `Inertia\Middleware` (não `Inertia\Http\Middleware` no v3).
- **2026-11-07 (Fase 7.1):** PD-701 a PD-704 aprovadas e registradas como D-031 a D-034. Inventário/domínios/hospedagem/repositórios/publicações manuais já entregues nas Fases 6.2+6.3 (sem duplicação). Sem deploy automático (P-008 em aberto).
- **2026-11-07 (Fase 7.2):** D-035 (sync síncrono, sem Job; cache nas colunas do banco). Webhook/health-check não iniciados (7.3/7.4).
- **2026-11-07 (Fase 7.3):** D-036 (webhook assinado idempotente). Health-check não iniciado (7.4).
- **2026-11-07 (Fase 7.4):** D-037 (SSRF pin + estados/ciclos + 06:30 com cron). Fase 7.5 não iniciada.
- **2026-11-07 (Fase 7.4 correção):** D-038 (runs persistentes por domínio/slot; runId no payload).
- **2026-11-07 (Reconciliação):** D-039 (backfill 7 dias + reenvio mesmo ID).
- **2026-11-07 (Fase 8):** D-040 (financeiro transacional).
- **2026-11-08 (Painéis):** D-042 (isolamento por ID + dashboard por perfil; ver `tests/Feature/PanelIsolationTest.php`).
- **2026-11-08 (Migração DB):** D-043 (Postgres/Supabase; ver `docs/OPERATIONS.md §16`).
- **2026-11-08 (Relatórios Comercial):** D-044 (Gate `view-reports` para `/finance/report`; ver `tests/Feature/FinancialTest.php`).
- **2026-11-08 (Meu Perfil):** D-045 (`/profile` leitura para todos os perfis; ver `tests/Feature/ProfileTest.php`).
- **2026-11-08 (Performance):** D-046 (agregações SQL + props enxutas; ver controllers).
- **2026-11-08 (Performance prod):** D-047 (OPcache/optimize/gzip/cache file/prefetch; ver `Dockerfile`, `docker/`, `render.yaml`, `sidebar.tsx`).
- **2026-11-08 (403 Developer):** D-048 (`ensure-admin`/`create-user` garantem perfil idempotente; comando para reparar: `php artisan webstudio:create-user <email> --role=DEVELOPER`).
- **2026-11-08 (Investigação):** D-049 (layout persistente + sessão cookie + PDO persistent; evidências: prod /login 2.7s vs local 0.06s).
