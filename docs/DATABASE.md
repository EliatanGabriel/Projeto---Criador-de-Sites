# DATABASE — WebStudio OS

**Versão:** 1.5 — Fase 6.2
**Fonte:** Doc.md v1.0 §6  
**Banco:** PostgreSQL 17 (driver `pgsql`, `pdo_pgsql`) + testes SQLite

## 1.1 Modelo implementado Fase 2

**Fase 6.2:** `websites(id uuid, client restrict, name index, description, type, status desenvolvimento/publicado/suspenso/manutencao/desativado index, started/published, technical_owner, technologies jsonb, notes, softDeletes)` + `website_projects(website/project cascade, relation origin/rework/maintenance/evolution, unique, partial unique origin)` + `hostings(id uuid, provider/name index, plan, account_identifier, status ativo/suspenso/cancelado, softDeletes)` + `website_domains(id uuid, website cascade, domain lower unique whereNotDeleted, is_primary partial unique, registrar, registered/expires, status ativo/suspenso/transferido/expirado, dns_notes, softDeletes)` + `website_environments(id uuid, website cascade, hosting nullOnDelete, name, type development/staging/production, url, status ativo/suspenso/offline/desativado, partial unique prod ativo, softDeletes)` + `website_repositories(id uuid, website cascade, environment nullOnDelete, provider, name, url unique whereNotDeleted, default_branch, softDeletes)` + `website_publications(id uuid, website cascade, environment restrict, version, published_at, responsible, description, commit_reference, result sucesso/falha/rollback, immutable)` + `maintenance_records(id uuid, website cascade, project/task nullable nullOnDelete, type corrigir/evoluir/seguranca/performance/conteudo/outro, description, responsible, status aberta/em_andamento/concluida/cancelada, opened/completed, notes, created_by, softDeletes)` + `credentials(id uuid, website/hosting/domain/environment nullable cascade, type hosting_admin/registrar/ftp/cms/service_token/outro, label, username, secret_encrypted encrypted, notes, last_rotated_at)` + `credential_access_logs(id uuid, credential cascade, user, action revealed/updated/created, ip, agent, created_at)`  
`project_templates(id uuid, name index, description, category index, version, is_active index, created/updated_by, timestamps, softDeletes)` + `project_template_phases(id uuid, template cascade, name, order)` + `project_template_tasks(id uuid, phase cascade, title, description, priority, suggested_role owner/developer, estimated_days, order, is_required)` + `projects.template_id/version/snapshot jsonb` + `tasks.template_task_id/phase_name/phase_order`
`projects(id uuid, demand_id unique restrict, client_id, title index, description, scope, technical_owner_id, started_at, planned_deadline, completed_at, status planning/in_progress/in_review/blocked/completed/cancelled index, priority low/medium/high/urgent, contracted_value, internal_notes, blocked_reason, converted_by/at, softDeletes)` + `project_members` + `project_histories` + `tasks(id uuid, project cascade, title, assignee/created_by, priority, status todo/in_progress/in_review/blocked/done/cancelled, started/due/completed, completed_by, blocked_reason, notes, softDeletes)` + `task_histories` + `task_comments` + `notifications(uuid, notifiable uuid)`
`demands(id uuid, title index, client_id restrictOnDelete, opportunity_id nullable nullOnDelete, commercial_owner_id, service_type, description, contracted_scope, negotiated_value numeric(12,2), contracted_at, desired_deadline, priority baixa/normal/alta/urgente, status draft/submitted/under_review/needs_information/approved/rejected/cancelled index, internal_notes, technical_owner_id, submitted/reviewed/approved/rejected timestamps, created_by, softDeletes)`
`demand_briefings(id uuid, demand_id unique cascade, objectives, target_audience, business_description, products_services, desired_pages, desired_features, references, visual_identity, colors_preferences, available_content, domain_info, hosting_info, additional_notes)`  
`demand_attachments(id uuid, demand_id cascade, disk local, path, original_name, mime, size, uploaded_by)`  
`demand_histories(id uuid, demand_id cascade index, user_id, event, description, from/to_status, metadata jsonb, timestamps)`  
`clients(id uuid, name, type pf/pj, document nullable index, email, phone, contact_name, address, city, state(2), zip, notes, status ativo/inativo index, created_by->users, timestamps, softDeletes)`
`opportunities(id uuid, title, client_id->clients nullOnDelete, contact_name, responsible_id->users, service_type, description, estimated_value numeric(12,2), negotiated_value numeric(12,2), opened_at date, expected_close_at date, status nova/em_contato/em_negociacao/aguardando_cliente/ganha/perdida/cancelada index, notes, created_by->users, timestamps, softDeletes)`  
`opportunity_activities(id uuid, opportunity_id cascadeOnDelete, user_id nullOnDelete, action, description, from_status, to_status, timestamps)`

## 1. Princípios

- Modelo relacional com chaves estrangeiras, índices, restrições e integridade referencial.
- Timestamps em todas as tabelas principais (`created_at`, `updated_at`).
- Índices em colunas de pesquisa/filtro/ordenação (nome, e-mail, status, datas, FKs).
- Impedir duplicidade de e-mail/CNPJ onde aplicável via unique constraints.
- Exclusão lógica (`deleted_at`) para registros importantes; sem hard delete em financeiro/auditoria.
- Transações obrigatórias em operações críticas (conversão demanda→projeto, geração de tarefas, movimentos financeiros).
- Separação lógica entre dados comerciais, projetos e usuários para preparação SaaS (sem multi-tenancy no MVP).

## 2. Tabelas Principais Aprovadas (Doc.md §6.1)

### 2.1 Usuários e permissões

| Tabela | Campos-chave | Observações |
|---|---|---|
| `users` | `id`, `name`, `email` (unique), `password` (hash), `role` ou FK [PENDENTE], `is_active`, `last_login_at`, `avatar_url`, `timestamps`, `deleted_at` | Cadastro apenas por administrador. Senha com bcrypt/argon2 (hash Laravel). |
| `roles` | `id`, `name`, `slug` (owner, commercial, developer) | Se usar Spatie, tabelas `roles`, `permissions`, `role_user`, `permission_role`, `model_has_roles` — decisão pendente. |
| `permissions` | `id`, `name`, `group` | Condicional. |
| `role_user` / `permission_role` | pivôs | Condicional. |
| `sessions` | Laravel session table (se usar `database` driver) | Controle de sessões (Doc.md Módulo 01). |
| `password_reset_tokens` | padrão Laravel | Recuperação de senha. |

### 2.2 Comercial e clientes

| Tabela | Campos-chave | Observações |
|---|---|---|
| `clients` | `id`, `company_name`, `trade_name`, `responsible_name`, `document` (CNPJ/CPF, unique quando presente), `email`, `phone`, `whatsapp`, `address` (jsonb ou colunas), `current_website`, `instagram`, `notes`, `origin`, `commercial_owner_id` (FK users), `status` (enum prospect/active/inactive/archived), `timestamps`, `deleted_at` | Cadastro centralizado (Doc.md Módulo 03). |
| `leads` | `id`, `client_id` (nullable, histórico), `contact_name`, `contact_email/phone`, `service_type`, `stage` (enum novo_contato/negociacao/proposta_enviada/aguardando/venda_fechada/venda_perdida), `expected_close_at`, `seller_id`, `source`, `notes`, `timestamps`, `deleted_at` | Um cliente pode ter vários leads históricos. Conversão sem duplicidade: checar `document`/`email` antes de criar cliente. |
| `lead_activities` | `id`, `lead_id`, `user_id`, `type`, `description`, `occurred_at` | Histórico de negociação. |
| `proposals` | `id`, `lead_id`/`client_id`, `title`, `status`, `valid_until`, `notes`, `timestamps` | Propostas comerciais. |
| `proposal_items` | `id`, `proposal_id`, `description`, `quantity`, `unit_price`, `total` | Itens com valores negociados. |

### 2.3 Demandas

| Tabela | Campos-chave | Observações |
|---|---|---|
| `demands` | `id`, `client_id` (FK), `commercial_owner_id` (FK users), `title`, `service_type` (enum), `description`, `objective`, `target_audience`, `desired_features` (jsonb), `visual_references`, `visual_identity`, `domain`, `hosting`, `requested_deadline`, `negotiated_value` (numeric 12,2), `commercial_conditions`, `notes`, `status` (enum rascunho/enviada/em_analise/aguardando_info/aprovada/recusada/convertida/cancelada), `converted_project_id` (nullable FK projects, unique), `submitted_at`, `reviewed_at`, `reviewed_by`, `timestamps`, `deleted_at` | Constraint: `converted_project_id` unique garante "uma demanda, no máximo um projeto". |
| `demand_briefings` | `id`, `demand_id`, `category`, `data` (jsonb), `timestamps` | Briefing estruturado por categoria (restaurante, institucional...). |
| `demand_attachments` | `id`, `demand_id`, `uploaded_file_id`, `original_name`, `mime`, `size`, `path`, `uploaded_by` | Anexos comerciais (logos, imagens, referências). |
| `demand_status_history` | `id`, `demand_id`, `from_status`, `to_status`, `user_id`, `comment`, `created_at` | Auditoria de fluxo (nunca apagar). |

### 2.4 Projetos

| Tabela | Campos-chave | Observações |
|---|---|---|
| `projects` | `id`, `client_id` (FK), `demand_id` (FK unique), `template_id` (FK), `title`, `project_type` (enum), `description`, `contracted_scope` (jsonb), `features` (jsonb), `technical_owner_id`, `priority` (enum), `started_at`, `deadline_at`, `contracted_value` (numeric), `status` (enum planejamento/aguardando_materiais/pronto_desenvolvimento/em_desenvolvimento/em_revisao/aguardando_cliente/preparado_publicacao/concluido/em_manutencao/cancelado), `github_repo_url`, `dev_url`, `prod_url`, `notes`, `timestamps`, `deleted_at` | Contrato nunca confundido com recebido (ver Financeiro). |
| `project_members` | `id`, `project_id`, `user_id`, `role` (owner/developer/commercial/observer), `timestamps` | Um projeto pode ter vários integrantes. |
| `project_tasks` | `id`, `project_id`, `title`, `description`, `status` (enum a_fazer/em_andamento/bloqueada/em_revisao/concluida), `priority` (enum baixa/normal/alta/urgente), `assignee_id` (FK users, nullable), `due_at`, `order`/`position`, `checklist` (jsonb), `estimated_hours`, `completed_at`, `created_from_template_task_id` (nullable), `block_reason`, `timestamps`, `deleted_at` | Cada tarefa vinculada a um projeto (obrigatório). |
| `task_comments` | `id`, `task_id`, `user_id`, `comment`, `created_at`, `updated_at` | Comentários. |
| `task_attachments` | `id`, `task_id`, `uploaded_file_id`, `uploaded_by` | Anexos de tarefas. |
| `project_activities` | `id`, `project_id`, `user_id`, `action`, `description`, `metadata` (jsonb), `created_at` | Histórico do projeto (não apagar). |

### 2.5 Templates

| Tabela | Campos-chave | Observações |
|---|---|---|
| `project_templates` | `id`, `name`, `category`, `description`, `checklist` (jsonb), `features` (jsonb), `technical_structure` (jsonb), `version`, `is_active`, `timestamps` | Templates de projeto (MVP). |
| `project_template_tasks` | `id`, `template_id`, `title`, `description`, `priority`, `estimated_hours`, `suggested_assignee_role`, `position`, `is_default` | Tarefas iniciais geradas na conversão. |
| `website_templates` | `id`, `name`, `category`, `description`, `github_repo`, `stack`, `status` | **Futuro (Fase 9)** — código reutilizável. Tabela prevista, sem implementação no MVP. |
| `website_template_versions` | `id`, `website_template_id`, `version`, `changelog`, `timestamps` | Futuro. |

### 2.6 Sites

| Tabela | Campos-chave | Observações |
|---|---|---|
| `websites` | `id`, `client_id`, `project_id` (origem), `name`, `primary_domain`, `hosting_provider`, `github_repo`, `prod_url`, `published_at`, `status` (enum em_desenvolvimento/publicado/suspenso/em_manutencao/desativado), `ssl_expires_at`, `technical_owner_id`, `maintenance_plan`, `notes`, `timestamps`, `deleted_at` | Inventário de sites entregues. |
| `website_domains` | `id`, `website_id`, `domain`, `is_primary`, `registrar`, `expires_at`, `auto_renew` | Um site pode ter vários domínios. |
| `website_deployments` | `id`, `website_id`, `environment` (staging/production), `commit_sha`, `deployed_at`, `deployed_by`, `status`, `url`, `notes` | Histórico de publicação (vários registros por site). |
| `website_maintenance_records` | `id`, `website_id`, `type`, `description`, `performed_at`, `performed_by`, `next_due_at` | Manutenção. |

### 2.7 Financeiro

| Tabela | Campos-chave | Observações |
|---|---|---|
| `financial_transactions` | `id`, `type` (receita/despesa), `category_id`, `client_id` (nullable), `project_id` (nullable), `description`, `amount` (numeric 12,2), `status` (previsto/pendente/recebido/pago/cancelado), `due_at`, `paid_at`, `currency` ('BRL'), `notes`, `created_by`, `timestamps`, `deleted_at` | **Sem hard delete.** Separar negociado/contratado/faturado/recebido/pendente. |
| `financial_categories` | `id`, `name`, `type`, `is_system` | Ex.: hospedagem, domínio, ferramentas, operacional. |
| `payment_records` | `id`, `transaction_id`, `amount`, `paid_at`, `method`, `reference`, `notes` | Parcelamentos/pagamentos parciais. |
| `expense_records` | `id`, `transaction_id` (despesa), `vendor`, `invoice_ref`, `due_at`, `paid_at` | Despesas efetivamente pagas apenas. |

### 2.8 Sistema

| Tabela | Campos-chave | Observações |
|---|---|---|
| `notifications` | padrão Laravel (`id`, `type`, `notifiable_type/id`, `data`, `read_at`) + customizações | Notificações internas; `read_at` para "marcar como lida". |
| `activity_logs` | `id`, `user_id`, `subject_type/id`, `event`, `description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at` | Auditoria de ações importantes. |
| `settings` | `id`, `key` (unique), `value` (text/jsonb), `group`, `timestamps` | Configurações da plataforma. |
| `uploaded_files` | `id`, `disk` ('s3'/'local'), `path`, `original_name`, `mime`, `size`, `uploaded_by`, `timestamps` | Metadados de uploads; uso em demandas/tarefas/projetos. |
| `failed_jobs` / `job_batches` | padrão Laravel | Filas. |
| `cache` / `sessions` | padrão Laravel (se driver database) | Cache/sessões. |

## 3. Relacionamentos (Doc.md §6.2)

- Cliente → vários leads históricos, várias demandas, vários sites, várias transações.
- Demanda → pertence a um cliente; no máximo um projeto (`demands.converted_project_id` unique ou `projects.demand_id` unique).
- Projeto → pertence a um cliente; várias tarefas; várias members; um template (nullable); vários anexos/atividades.
- Tarefa → pertence a um projeto; vários comentários/anexos.
- Site → vários domínios; vários deployments; vários registros de manutenção.
- Transação financeira → pode relacionar-se a cliente ou projeto (nullable FKs).
- Template → várias template tasks.

## 4. Integridade e Índices

- `unique`: `users.email`, `clients.document` (where not null), `demands.converted_project_id` / `projects.demand_id`, `settings.key`, `website_domains.domain`.
- `FKs` com `on delete restrict` em financeiro/auditoria; `cascade` apenas em pivôs filhos (ex.: `project_template_tasks` quando template deletado).
- Índices: `clients(status)`, `demands(status, client_id, submitted_at)`, `projects(status, client_id, deadline_at)`, `project_tasks(project_id, assignee_id, status, due_at)`, `financial_transactions(type, status, due_at)`, `websites(client_id, status)`.
- Tipos monetários: `numeric(12,2)` (nunca float). Datas sempre `timestamptz`/`timestamp` com timezone America/Sao_Paulo na aplicação.
- Enums: usar enums PostgreSQL **ou** check constraints + enums PHP. Recomendação inicial: `varchar` + cast PHP Enum com valores padronizados (evita migração complexa de enum PG).
- JSONB para briefing, escopo, features, metadata.

## 5. Modelo Inicial — DDL Conceitual (Fase 1)

Migrations devem seguir ordem de dependência: `users` → `clients` → `leads/proposals` → `demands` → `project_templates` → `projects` → `project_members` → `project_tasks` → `websites` → `financial_*` → `notifications/activity_logs/settings/uploaded_files`.

Seeders iniciais (apenas para desenvolvimento/staging local, nunca produção com dados fictícios): papéis (owner, commercial, developer), usuário Eliatan (owner), template "Institucional padrão" com tarefas mínimas (planejamento, wireframe, layout, desenvolvimento, revisão, publicação).

## 6. Preparação SaaS (Doc.md §6.4)

- Não implementar multi-tenancy no MVP, mas evitar identificadores globais exclusivos que impeçam separação futura.
- Colunas `deleted_at` (soft deletes) e `created_by`/`updated_by` facilitam auditoria e futuro escopo por organização.
- **[PENDENTE]** UUID v7 vs bigint: recomendar UUID v7 em `DECISIONS.md` (ordenável, preparado para SaaS). Decisão final antes da primeira migration.
