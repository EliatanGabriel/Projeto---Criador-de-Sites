# PHASE 6 — Modelagem e Arquitetura de Sites e Ativos Digitais

**Status:** 6.1 — Design (sem implementação)  
**Data:** 2026-11-07  
**Fonte:** Doc.md §5 Módulos 10, §6.1; AGENTS.md; `docs/DATABASE.md`, `docs/SECURITY.md`, `docs/PRD.md`

> **Escopo desta fase:** somente inspeção, análise, modelagem e documentação. Nenhum código funcional será alterado nesta etapa. A fase de implementação será denominada Fase 6.2.

---

## 1. Diagnóstico da arquitetura atual

### 1.1 Base já aprovada e verificada (Fases 0–5)

| Camada | Estado verificado |
|---|---|
| **Stack** | Laravel 13 / React 19 / Inertia v3 / PostgreSQL 17 / Tailwind v4 / shadcn/ui / Fortify / Spatie Permission 7 / Pest 4 |
| **Identidade** | UUID v7 em todas as tabelas (`$table->uuid('id')->primary()`, `HasUuids`, `uniqueId(): Str::uuid7()`) |
| **SoftDeletes** | `clients`, `demands`, `projects`, `tasks`, `project_templates` |
| **Histórico** | `demand_histories`, `project_histories`, `task_histories` + `task_comments`; `opportunity_activities` — imutáveis via UI |
| **Políticas** | `ClientPolicy`, `DemandPolicy`, `ProjectPolicy` (OWNER total; COMMERCIAL só próprios; DEVELOPER só projetos com acesso), `TaskPolicy`, `ProjectTemplatePolicy`, `UserPolicy` |
| **Controller pattern** | Controller fino → FormRequest → Policy → Action transacional (`ConvertDemandToProjectAction`, `TransitionDemandAction`) |
| **Notificações** | `database` (sem e-mail), `notifications` table com `notifiable_id uuid` |
| **Uploads** | `local` privado, whitelist MIME, 10 MB, download via policy |
| **Anexos/briefings** | `demand_attachments`, `demand_briefings` (1—1 com colunas), referenciais para `projects` |

### 1.2 Relevante para Sites

- `Client` **não** possui relação `websites` ainda; possui `opportunities`, `demands`, `projects`.
- `Project` possui `client_id`, `demand_id UNIQUE`, `template_id/version/snapshot`, `tasks(project_id cascade)`, `members(project_id,user_id unique)`, `histories`. **Não** possui `website_id` — é o elo ausente.
- `Task` possui `project_id cascade`, `assignee`, `phase_name/phase_order` (preservação pós-template), `template_task_id` trace.
- Permissões já existem mas subutilizadas: `websites.view/manage` (OWNER + DEVELOPER `view`, COMMERCIAL sem `websites.*` — apenas `projects.view` restrito). `RolePermissionSeeder` já prevê `websites.view/manage`; nenhum controller/rota usa hoje.
- `DATABASE.md §2.6` previa `websites / website_domains / website_deployments / website_maintenance_records` — nomes legados; esta proposta substitui por modelo mais granular sem sobrecarregar a tabela `websites` (recusa de "mega-tabela" com colunas de domínio/hospedagem/ambiente/repo — ver §6).
- Nenhuma camada de credenciais: nenhum campo criptografado, nenhum `hidden` para secrets, nenhum cast `encrypted`, nenhuma política de acesso a secrets.

### 1.3 Convenções obrigatórias para a Fase 6.2

- UUID v7 + SoftDeletes em entidades de negócio; **sem SoftDeletes** em tabelas de histórico/auditoria e credenciais (auditoria nunca apaga).
- Enums como `varchar` + `check`/validação PHP (não PG ENUM), alinhado a `DemandStatus`, `ProjectStatus`, `TaskStatus`.
- Relações com `restrictOnDelete` para FKs de preservação histórica, `cascadeOnDelete` apenas para filhos descartáveis (fases/tarefas de template, members) — nunca para `websites/finance/auditoria`.
- `Fillable` estrito, `$hidden` para secrets, casts `encrypted`/`array`/`datetime` quando aplicável.
- Rotas Inertia aninhadas (`/sites/{site}/domains`) com `can:` + Policy; Actions para publicações e manutenção transacional.

---

## 2. Conceitos e regras de negócio

### 2.1 Site — produto digital sob gestão

- Um **site** é o ativo durável: continua existindo após a entrega do projeto inicial. É distinto do **projeto** (esforço de produção) e da **demanda** (pedido comercial).
- Um site **pertence** a exatamente um cliente (`client_id` obrigatório, `restrictOnDelete`).
- Um site **não exige** um único projeto: pode nascer de um projeto inicial (`origin_project_id` nullable) e receber projetos posteriores de reformulação/manutenção via pivô `website_projects`. A relação é N—N, com metadados `relation_type` (`origin`, `rework`, `maintenance`, `evolution`) e datas.
- `website_projects.demand_id` opcional para rastrear a demanda executada em cada projeto vinculado.
- Status operacional: `desenvolvimento`, `publicado`, `suspenso`, `manutencao`, `desativado`. Transições controladas (sem "qualquer→qualquer" via formulário; usar enum validado).
- Publicação exige ambiente `production` ativo (regra de aplicação, não só índice).

### 2.2 Domínios

- Um site possui **N domínios** (principal + adicionais/aliases + redirecionamentos).
- Exatamente **zero ou um** principal por site em determinado momento (índice parcial único).
- Domínio é entidade própria com registrar, ciclo de vida, status e notas DNS. Não é atributo do site.

### 2.3 Hospedagens

- **Entidade independente** (não "coluna" do site): uma conta de hospedagem pode hospedar N ambientes de N sites.
- Responsabilidade administrativa: provedor, plano, conta, status, datas. Sem credenciais em texto comum (ver §5).

### 2.4 Ambientes

- Um site possui **N ambientes** (`development`, `staging`, `production`). Cada ambiente referencia **uma** hospedagem (ou nenhuma em desenvolvimento local).
- Máximo **um `production` ativo** por site (índice parcial único em `(site_id, type) WHERE is_active` — ou via `status = ativo`).

### 2.5 Repositórios

- Um site possui **N repositórios** (frontend/backend separados, monorepos, mirrors). Opcionalmente vinculados a um ambiente.
- Metadados apenas; sem integração operacional GitHub nesta fase.

### 2.6 Publicações

- Registro **manual** e imutável: site + ambiente + versão + data + responsável + commit + resultado. É o diário de deploy, não automação.
- Resultados: `sucesso`, `falha`, `rollback`. Publicação bem-sucedida em `production` atualiza `websites.published_at`.

### 2.7 Manutenção

- **Não criar segundo sistema de tarefas.** Manutenção é orchestrada via **projetos/tarefas existentes**: uma `maintenance_record` pode apontar opcionalmente para um `project_id`/`task_id` de correção.
- Ciclo: abertura → execução via tarefas → conclusão. `websites.status = manutencao` denormalizado enquanto houver manutenção aberta.

---

## 3. Entidades e atributos

### 3.1 `websites`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | UUID v7 |
| `client_id` | uuid FK `clients` `restrict` | `index` |
| `origin_project_id` | uuid FK `projects` nullable `nullOnDelete` | Projeto fundador (pode ser nulo para sites legados) |
| `name` | string `index` | Ex.: "Site Institucional Loja X" |
| `description` | text nullable | |
| `type` | string nullable | `institucional`, `landing_page`, `loja_virtual`, `portfolio`, `custom`, `outros` (string livre para evolução) |
| `status` | enum `desenvolvimento/publicado/suspenso/manutencao/desativado` `index` | Status operacional |
| `started_at` | date nullable | Início do ciclo de vida do site |
| `published_at` | date nullable | Primeira publicação em produção |
| `technical_owner_id` | uuid FK `users` nullable `index` | Responsável técnico atual |
| `technologies` | jsonb nullable | `["laravel","react","postgresql"]` — lista livre |
| `notes` | text nullable | Observações internas |

`timestamps`, `softDeletes`.

**Relações:** `client`, `originProject`, `projects` (via `website_projects`), `domains`, `environments`, `repositories`, `publications`, `maintenanceRecords`, `credentials`.

### 3.2 `website_projects` (pivô)

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | Auditável |
| `website_id` | uuid FK `websites` `cascade` `index` | |
| `project_id` | uuid FK `projects` `cascade` `index` | |
| `relation_type` | enum `origin/rework/maintenance/evolution` | Papel do projeto no site |
| `registered_at` | date nullable | Quando o vínculo foi reconhecido |

`unique(website_id, project_id)`, `timestamps`. Sem SoftDeletes (pivô histórico nunca apaga).

### 3.3 `website_domains`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites` `cascade` `index` | |
| `domain` | string `unique` `index` | Armazenar minúsculo, sem `www`/protocolo |
| `is_primary` | boolean `index` | Apenas um `true` por `website_id` (partial unique) |
| `registrar` | string nullable | Ex.: "Registro.br", "Hostinger", "Cloudflare" |
| `registered_at` | date nullable | |
| `expires_at` | date nullable `index` | Alerta de expiração (30/60 dias) |
| `status` | enum `ativo/suspenso/transferido/expirado` | |
| `dns_notes` | text nullable | Registros relevantes, notas de propagação |

`timestamps`, `softDeletes` (domínios desativados permanecem para auditoria).

### 3.4 `hostings`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `provider` | string `index` | Nome comercial do provedor |
| `name` | string `index` | Nome interno da conta/serviço |
| `plan` | string nullable | Plano contratado |
| `account_identifier` | string nullable | Identificador **não-sensível** da conta (ex.: ID da conta, e-mail de acesso sem senha) |
| `status` | enum `ativo/suspenso/cancelado` | |
| `notes` | text nullable | |

Entidade independente, reutilizável por vários ambientes. `timestamps`, `softDeletes`. Sem credenciais aqui (ver `credentials`).

### 3.5 `website_environments`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites` `cascade` `index` | |
| `hosting_id` | uuid FK `hostings` nullable `nullOnDelete` `index` | Hospedagem do ambiente |
| `name` | string | Ex.: "Produção", "Homologação" |
| `type` | enum `development/staging/production` `index` | |
| `url` | string nullable | URL do ambiente |
| `status` | enum `ativo/suspenso/offline/desativado` | `offline` = fora do ar temporariamente |
| `notes` | text nullable | |

`timestamps`, `softDeletes`. Índice parcial único: `unique(website_id) WHERE type='production' AND status='ativo'`.

### 3.6 `website_repositories`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites` `cascade` `index` | |
| `environment_id` | uuid FK `website_environments` nullable `nullOnDelete` `index` | Quando há um repo por ambiente |
| `provider` | string nullable | `github`, `gitlab`, `bitbucket`, `outros` |
| `name` | string | Nome do repositório |
| `url` | string `unique` | URL canônica |
| `default_branch` | string nullable | Ex.: `main` |
| `notes` | text nullable | |

`timestamps`, `softDeletes`.

### 3.7 `website_publications`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites` `cascade` `index` | |
| `environment_id` | uuid FK `website_environments` `restrict` `index` | Ambiente publicado |
| `version` | string nullable `index` | Tag/versão/release |
| `published_at` | timestamp `index` | Data/hora do registro |
| `responsible_id` | uuid FK `users` nullable `index` | Quem registrou |
| `description` | text nullable | |
| `commit_reference` | string nullable | SHA/tag quando disponível |
| `result` | enum `sucesso/falha/rollback` | Resultado observado |
| `notes` | text nullable | |

Sem SoftDeletes — **histórico imutável**. `timestamps` (apenas `created_at`; `updated_at` nullable ou ausente — proposta: apenas `created_at`).

### 3.8 `maintenance_records`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites` `cascade` `index` | |
| `project_id` | uuid FK `projects` nullable `nullOnDelete` | Projeto de execução (quando houver) |
| `task_id` | uuid FK `tasks` nullable `nullOnDelete` | Tarefa pontual (opcional) |
| `type` | enum `corrigir/evoluir/seguranca/performance/conteudo/outro` `index` | |
| `description` | text | |
| `responsible_id` | uuid FK `users` nullable `index` | |
| `status` | enum `aberta/em_andamento/concluida/cancelada` `index` | |
| `opened_at` | date `index` | |
| `completed_at` | date nullable | |
| `notes` | text nullable | |
| `created_by` | uuid FK `users` nullable | |

`timestamps`, `softDeletes`. Sem segundo motor de tarefas — ver §9.

### 3.9 `credentials`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `website_id` | uuid FK `websites` nullable `cascade` `index` | Credencial de site |
| `hosting_id` | uuid FK `hostings` nullable `cascade` `index` | Credencial de hospedagem |
| `domain_id` | uuid FK `website_domains` nullable `cascade` `index` | Credencial de registrador |
| `environment_id` | uuid FK `website_environments` nullable `cascade` `index` | Credencial de ambiente |
| `type` | enum `hosting_admin/registrar/ftp/cms/service_token/outro` `index` | |
| `label` | string | "Painel principal Hostinger" |
| `username` | string nullable | Identificador não-sensível |
| `secret_encrypted` | text | `encrypted` cast (AES-256-CBC via `APP_KEY`) |
| `notes` | text nullable | Sem secrets aqui |
| `last_rotated_at` | timestamp nullable | |

Sem SoftDeletes. **Constraint de aplicação:** exatamente um dos `*_id` deve ser preenchido (validar em Request/Action; banco pode usar `CHECK` parcial em PG, mas não é obrigatório nesta fase — documentar).

### 3.10 `credential_access_logs`

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | uuid PK | |
| `credential_id` | uuid FK `credentials` `cascade` `index` | |
| `user_id` | uuid FK `users` `nullOnDelete` `index` | Quem visualizou/alterou |
| `action` | enum `revealed/updated/created` | |
| `ip_address` | string(45) nullable | |
| `user_agent` | text nullable | |

Apenas `created_at`. Sem update/delete — auditoria pura.

---

## 4. Diagrama ER (Mermaid) e cardinalidades

```mermaid
erDiagram
    CLIENTS ||--o{ WEBSITES : possui
    CLIENTS ||--o{ PROJECTS : possui
    PROJECTS ||--o{ WEBSITE_PROJECTS : vinculado
    WEBSITES ||--o{ WEBSITE_PROJECTS : recebe
    PROJECTS ||--o{ TASKS : dividido
    PROJECTS ||--o{ PROJECTS : demand_id=NULL(1:1 lógica)
    DEMANDS ||--|| PROJECTS : convertido
    WEBSITES ||--o{ WEBSITE_DOMAINS : tem
    WEBSITES ||--o{ WEBSITE_ENVIRONMENTS : tem
    HOSTINGS ||--o{ WEBSITE_ENVIRONMENTS : hospeda
    WEBSITES ||--o{ WEBSITE_REPOSITORIES : tem
    WEBSITE_ENVIRONMENTS ||--o{ WEBSITE_REPOSITORIES : arquiva
    WEBSITES ||--o{ WEBSITE_PUBLICATIONS : publica
    WEBSITE_ENVIRONMENTS ||--o{ WEBSITE_PUBLICATIONS : alvo
    WEBSITES ||--o{ MAINTENANCE_RECORDS : mantém
    PROJECTS ||--o{ MAINTENANCE_RECORDS : executa
    TASKS ||--o{ MAINTENANCE_RECORDS : detalha
    WEBSITES ||--o{ CREDENTIALS : acessa
    HOSTINGS ||--o{ CREDENTIALS : acessa
    WEBSITE_DOMAINS ||--o{ CREDENTIALS : acessa
    WEBSITE_ENVIRONMENTS ||--o{ CREDENTIALS : acessa
    CREDENTIALS ||--o{ CREDENTIAL_ACCESS_LOGS : auditado
```

**Cardinalidades resumidas:**

- `1 CLIENTS — N WEBSITES (restrictOnDelete)`
- `1 WEBSITES — N WEBSITE_PROJECTS — N PROJECTS` (N—N com papel; `origin_project_id` em `websites` é atalho para o primeiro vínculo, não substitui o pivô)
- `1 WEBSITES — N WEBSITE_DOMAINS` (0..1 primary; `unique(domain)`)
- `1 HOSTINGS — N WEBSITE_ENVIRONMENTS` (entidade reutilizável)
- `1 WEBSITES — N WEBSITE_ENVIRONMENTS` (0..1 `production ativo`)
- `1 WEBSITES — N WEBSITE_REPOSITORIES` (url única)
- `1 WEBSITES + 1 ENVIRONMENT — N WEBSITE_PUBLICATIONS` (imutável)
- `1 WEBSITES — N MAINTENANCE_RECORDS — 0..1 PROJECT/TASK`
- `1 CREDENTIALS — N CREDENTIAL_ACCESS_LOGS` (imutável)

**FKs e índices:**

- Todas FKs `uuid` com `index`; FKs históricas com `restrict` (websites, demands, projects, publications); filhos descartáveis com `cascade` (phases/tasks de template→projeto já resolvido; domains/environments/repos por site).
- Unicidades: `website_domains.domain unique`, `website_repositories.url unique`, `website_projects(website_id,project_id) unique`, `projects.demand_id unique` (existente).
- Parciais (PG): primário único por site, produção ativa única por site.
- `jsonb` apenas em `technologies` (lista) e não em colunas sensíveis.

**Exclusão/preservação:** SoftDeletes em `websites`, `website_domains`, `hostings`, `website_environments`, `website_repositories`, `maintenance_records`. **Sem** SoftDeletes em `website_projects`, `website_publications`, `credentials`, `credential_access_logs` (preservação de auditoria).

---

## 5. Estratégia de segurança e credenciais

### 5.1 Princípios

- Sem `text` livre para segredos: `secret_encrypted` com cast `encrypted` (Laravel `Crypt`, AES-256-CBC, chave `APP_KEY`). Nunca `string`/`text` puro.
- `secret` nunca aparece em JSON Inertia: campo `$hidden` no Model + `Resource`/`toArray` que o omite. Exposição apenas por rota dedicada.
- Reveal é **explícito, auditável e temporário**: rota `POST /credentials/{credential}/reveal` com `password.confirm` (re-autenticação), `can('credentials.view')`, log em `credential_access_logs` (ação `revealed`), resposta com header `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, e `Content-Disposition` não necessário — o frontend só exibe por segundos.
- Mascaramento por padrão na interface (`••••••••`), sem "mostrar" sem re-auth.
- Sem log de valores: `Log::` e exceções nunca incluem `secret`; `metadata` de históricos nunca recebe secretes; testes garantem ausência em `content` de resposta.
- `APP_KEY` precisa ser compartilhado entre nós (dev/staging/prod) com rotação documentada; perda = segredos irrecuperáveis (alertar em OPERATIONS).

### 5.2 Autorização específica

- Permissão separada `credentials.view` (OWNER apenas), distinta de `websites.view`. COMMERCIAL nunca vê credenciais; DEVELOPER nunca vê credenciais administrativas por padrão (ver §6).

### 5.3 Proteção contra exposição

- `fillable` nunca inclui `secret_encrypted` via input cru — FormRequest valida `secret` em texto puro apenas na escrita (criptografado em transit), resposta nunca devolve.
- Formulários de edição nunca pré-preenchem segredos (campo vazio por padrão, "deixar em branco para não alterar").
- `php artisan route:list -v` e `storage/logs` auditados em CI (grep por `secret_encrypted` fora de migrations/models).

---

## 6. Matriz de permissões proposta

Novas permissões (adicionais a `websites.view/manage` já semeadas):

- `websites.view` (leitura), `websites.manage` (CRUD + domínios/hospedagens/ambientes/repos/publicações/manutenção), `websites.publications` (registrar publicações — subconjunto de manage), `credentials.view` (revelar/utilizar credenciais, OWNER).

| Permissão | OWNER | COMMERCIAL | DEVELOPER |
|---|---|---|---|
| `websites.view` | ✅ todos | ✅ só clientes próprios | ✅ só projetos com acesso |
| `websites.manage` | ✅ | ❌ | ❌ |
| `websites.publications` | ✅ | ❌ | ✅ projetos acessíveis |
| `credentials.view` | ✅ | ❌ | ❌ (padrão) |
| `websites.maintenance` | ✅ | ❌ | ✅ criar em projetos acessíveis |

**Regras de escopo (Policy):**

- `WebsitePolicy::view`: OWNER todos; COMMERCIAL apenas sites cujo `client` possui oportunidade/demanda com `commercial_owner_id = user`; DEVELOPER apenas sites com projeto onde é `technical_owner`, `member` ou tem tarefa atribuída.
- `Domain/Hosting/Environment/Repository` herdam `WebsitePolicy::view` do site pai; escrita exige `warnings.manage`.
- `CredentialPolicy`: `view` exige `credentials.view` (OWNER). COMMERCIAL/DEVELOPER → 403 sempre (mesmo que tenham `websites.view`).
- `PublicationPolicy::create`: OWNER ou DEVELOPER com projeto acessível no site.
- Rotas sempre `can:` + policy item; sidebar ocultação é apenas UX.

**Compatibilidade com o existente:** `ProjectPolicy` já implementa escopo COMMERCIAL/DEVELOPER por `demand/commercial_owner` e `technical_owner/member/tasks` — `WebsitePolicy` reutiliza a mesma lógica via `project_id`/`website_projects`. Nenhuma permissão existente é removida; `templates.view` para DEVELOPER permanece (somente leitura).

---

## 7. Proposta de interface

Seguindo shadcn/ui, Inertia, responsiva, pt-BR:

```
Sites (/sites)
├── Listagem: nome, cliente, tipo, status, domínio principal, produção URL, responsável, renovação (expires_at)
├── CRUD: /sites/create, /sites/{site}/edit
└── Detalhe: /sites/{site}
    ├── Aba Visão geral: dados, projetos vinculados, progresso de manutenção
    ├── Aba Domínios: tabela + CRUD aninhado
    ├── Aba Hospedagens/Ambientes: ambientes com hospedagem vinculada, CRUD de ambientes
    ├── Aba Repositórios: tabela + CRUD aninhado
    ├── Aba Publicações: histórico cronológico + "Registrar publicação" (manual)
    ├── Aba Manutenção: registros abertos/concluídos + "Nova manutenção" (vincula project/task)
    ├── Aba Tarefas: reutiliza agrupamento por phase_name do projeto (sem novo sistema)
    └── Aba Acesso (credenciais): lista mascarada, botão "Revelar" com confirmação de senha + alerta de auditoria
```

- Listagens com `search` (nome/cliente/domínio), filtros `status`, `client_id`, `type`; paginação 15.
- Badges de status, progresso de manutenção (abertas vs. total), alertas de expiração de domínio (30/60 dias).
- Estados vazios informativos, confirmação em ações destrutivas, erros de validação pt-BR, dados BRL/BR como padrão.

---

## 8. Relacionamento com projetos e tarefas (técnico)

- `website_projects(website_id, project_id, relation_type)` suporta **site com vários projetos** (origin + reworks). `websites.origin_project_id` é apenas ponteiro de conveniência para o primeiro projeto.
- **Conversão demanda→projeto** não cria `website` automaticamente (decisão a confirmar — ver §10): OWNER vincula explicitamente um site existente ou cria um novo na página `/sites/create` selecionando o projeto de origem. Isso evita automação indesejada.
- **Manutenção:** `maintenance_records.project_id/task_id` opcionais apontam para tarefas existentes (`tasks` com `project_id`). Um registro de manutenção pode gerar uma tarefa via mesmo fluxo de `tasks.create` (sem mecanismo paralelo).
- **Progresso:** reutilizar `Project::progressData()`; progresso do site = agregação dos projetos vinculados (ponderação a definir — ver §11).
- **Notificações:** reutilizar `DatabaseNotification` (`TaskAssigned`, `TaskBlocked` etc.) para manutenção/publicação (ex.: manutenção aberta → técnico).

---

## 9. Ordem recomendada de implementação (Fase 6.2, não executada)

1. **Migrations + Models + Enums + Factories** (sem UI): `websites`, `website_projects`, `domains`, `hostings`, `environments`, `repositories`, `publications`, `maintenance_records`, `credentials`, `credential_access_logs`.
2. **Backend leitura/escrita** (`WebsitePolicy`, Requests, Controllers aninhados) + testes de escopo por perfil.
3. **Frontend leitura** (listagem + detalhe) + testes Inertia.
4. **Publicações manuais + Manutenção (integração com tasks)** + testes transacionais.
5. **Credenciais (encrypted + reveal auditado)** + testes de não-exposição em JSON/logs.
6. **Sidebar, dashboard e docs** (atualizar `DATABASE/SECURITY/PRD/DECISIONS`).

Cada passo sem avançar sem validação em SQLite + PG17 + `typecheck` + `build`.

---

## 10. Decisões arquiteturais pendentes

> **Não implementadas nesta fase. Registradas para deliberação antes da 6.2.**

| ID | Tema | Opções | Recomendação |
|---|---|---|---|
| PD-061 | `website_projects` vs `origin_project_id` único | (A) pivô N—N + atalho; (B) apenas `origin_project_id` + `maintenance` sem vínculo | **(A)** pivô N—N desde o início (documentado acima) |
| PD-062 | Hospedagem global vs por site | (A) entidade independente reutilizável; (B) transação por site | **(A)** entidade `hostings` |
| PD-063 | Credenciais com Laravel `encrypted` cast vs campo texto + KMS | (A) `encrypted` nativo; (B) KMS externo | **(A)** nesta fase; evoluir para KMS na Fase SaaS |
| PD-064 | Unicidade de domínio | (A) `unique(domain)` global; (B) única por site | **(A)** global minúsculo |
| PD-065 | Conversão automática de projeto em site | (A) manual explícita; (B) automática na conversão | **(A)** manual explícita |
| PD-066 | Progresso do site | (A) média ponderada dos projetos; (B) só projetos ativos; (C) sem progresso na 6.2 | **(C)** na 6.2, definir na 6.3 |
| PD-067 | `websites` seeder de tipos | (B) lista fechada evolutiva | **(B)** string livre + constante de sugestões |

---

## 11. Riscos técnicos

| # | Risco | Impacto | Mitigação |
|---|---|---|---|
| 1 | Vazamento de credenciais (JSON/log/exceção) | Crítico | `encrypted` + `$hidden` + rota reveal auditada + retorno sem secret |
| 2 | `APP_KEY` perdido/rotacionado sem aviso | Alto (segredos irrecuperáveis) | Documentar em OPERATIONS; teste de criptografia |
| 3 | Domínio com dois primários | Médio | Índice parcial único PG + teste concorrente |
| 4 | Duas produções ativas por site | Médio | Índice parcial único + validação de `type/status` |
| 5 | Mega-tabela `websites` (domínio/hospedagem/ambiente/repo juntos) | Alto (drag) | Entidades separadas propostas |
| 6 | Escopo COMMERCIAL/DEVELOPER vazando sites de outros clientes | Alto | `WebsitePolicy` escopo por projeto/demanda + testes por perfil |
| 7 | Manutenção em sistema paralelo de tarefas | Médio | Obrigatoriedade de reutilizar `tasks`/`projects` |
| 8 | Snapshot de template conflitando com `website_projects` | Baixo | `template_id/version/snapshot` já isolados; sem impacto |

---

## 12. Assinatura de conformidade

- Nenhuma migration, Model, Controller, Request, Policy ou componente React foi alterado nesta entrega (6.1).
- Padrões preservados: UUID v7, SoftDeletes, histórico imutável, `lockForUpdate` + UNIQUE na conversão, policies no backend, shadcn/ui/Inertia.
- Próximo passo **não** executado sem aprovação: Fase 6.2 (implementação).

---

*Fim de `docs/PHASE-6-DESIGN.md` — aguardando aprovação para Fase 6.2.*
