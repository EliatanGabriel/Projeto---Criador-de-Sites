# DECISIONS — WebStudio OS

**Versão:** 1.5 — Fase 5 (atualizado)
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
