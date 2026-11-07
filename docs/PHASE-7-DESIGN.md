# PHASE 7 — Modelagem e Arquitetura da Integração GitHub e Publicação Assistida

**Versão:** 7.5.1
**Status:** Fase 7 concluída em PG17.11 — 190 testes (incl. integração multi-domínio da ressalva 7.4 e reconciliação 7 dias)
**Data:** 2026-11-07
**Fonte:** Doc.md §5 Módulo 10, §9.3, §10/Fase 7; `docs/DECISIONS.md` D-030–D-039; `PHASE-6-DESIGN`, `PHASE-6.3-DESIGN`, `DATABASE`, `SECURITY`, `ARCHITECTURE`

> **Escopo desta entrega (7.1):** somente fechamento de decisões e documentação da arquitetura. Nenhuma migration, Model, Controller, Job, rota ou integração GitHub foi criada ou alterada. A implementação começa na Fase 7.2 após aprovação explícita. Nenhuma funcionalidade existente foi modificada e nenhum inventário foi duplicado.

---

## 1. Diagnóstico da base existente (Fases 0–6.3)

| Camada | Estado verificado |
|---|---|
| **Sites** | `Website` (1 cliente `restrict`, status `desenvolvimento/publicado/suspenso/manutencao/desativado`, `technologies jsonb`, `published_at`) |
| **Vínculo projeto↔site** | `website_projects` N—N pivô fonte única (`origin/rework/maintenance/evolution`, origem única parcial), `projects.demand_id UNIQUE` (D-030) |
| **Domínios** | `WebsiteDomain` (`domain lower unique`, `is_primary` parcial único, `expires_at` → alertas) |
| **Hospedagens** | `Hosting` independente reutilizável (`next_renewal_at` na 6.3) |
| **Ambientes** | `WebsiteEnvironment` (`type development/staging/production`, parcial único produção ativa, `ssl_expires_at` na 6.3) |
| **Repositórios** | `WebsiteRepository` (`website_id`, `environment_id nullable`, `provider`, `name`, `url unique`, `default_branch`) — **metadados apenas** (PHASE-6-DESIGN §2.5) |
| **Publicações** | `WebsitePublication` **manual e imutável** (`environment restrict`, `version`, `published_at`, `responsible`, `commit_reference`, `result sucesso/falha/rollback`) — diário de deploy, não automação |
| **Progresso/alertas** | `CalculateWebsiteProgressAction`, `website_alerts` (fingerprint único parcial onde `status='open'`), `CheckExpirationsAction` + `CheckExpirationsJob` diário 07:00 `America/Sao_Paulo` |
| **Credenciais** | `Credential` (`encrypted` cast, `hidden`, CHECK 1-of-4 PG, `CredentialPolicy` OWNER-only) + `CredentialAccessLog` imutável, reveal com `password.confirm` + `throttle:5,1` |
| **AuthZ** | Spatie 7, Policies no backend (`WebsitePolicy`, `HostingPolicy`, `CredentialPolicy`, `WebsiteAlertPolicy`), FormRequests, Inertia sem API REST geral (só webhook externo justificável) |
| **Stack** | Laravel 13 / React 19 / Inertia v3 / PG17 / Tailwind v4 / shadcn/ui / filas `database` + `WithoutOverlapping` |

### 1.1 Lacunas para a Fase 7

- `website_repositories` sem sincronização ativa: sem `last_commit_*`, sem estado GitHub, sem botão "Sincronizar".
- Publicações manuais sem assistência: sem checklist pré-publicação, sem fonte (`manual`/`webhook`), sem vínculo com GitHub `deployment`/`deployment_status`, sem evidência de saúde/rollback.
- Sem endpoint de webhook GitHub (assinatura, idempotência, rastreabilidade).
- Sem health-check HTTP/SSL diário com anti-SSRF (alertas 6.3 cobrem vencimentos declarados, não disponibilidade nem validade real do certificado).

### 1.2 Conflitos verificados (nenhum bloqueante)

| Tema | Situação | Resolução |
|---|---|---|
| Inventário duplicado | Roadmap Fase 7 lista inventário, já entregue 6.2/6.3 | Reaproveitar tabelas; não criar `website_deployments` legado |
| Publicações imutáveis × `pending` | PHASE-6-DESIGN impõe imutabilidade | `pending` só para implantação iniciada; transição única até terminal, depois imutável (ver §3) |
| API REST geral × webhook | D-003 veda REST geral | `POST /webhooks/github` é a **única exceção** justificável (integração externa assinada), conforme `ARCHITECTURE §1` |
| Deploy automático | §9.3 prevê "deploy automático após aprovação" | D-033 adia (P-008 indefinido); design permite evolução sem assumir provedor |
| Segredos | D-030 credenciais OWNER-only | Token GitHub e segredo do webhook vão para `credentials`, nunca em coluna de texto comum |

---

## 2. Arquitetura da integração GitHub

```
[GitHub] --(webhook assinado)--> [POST /webhooks/github] (routes, sem sessão)
[WebStudio] --(API REST)--> [GitHub API] (PAT em credentials, OWNER-only)
[WebsiteRepository] --cache--> [GitHubClient] --(filas database)--> [SyncRepositoriesJob]
[WebhookEvent] --(transição)--> [WebsitePublication pending→terminal]
[HealthCheckJob 06:30] --(HTTP/SSL)--> [WebsiteHealthCheck] (+ manifesto em site)
```

- **Monólito modular (§4.2):** todo o código no domínio `Websites` (`Actions/Services/Jobs/Queries`), controllers finos, sem microsserviço. Fila `database` (D-021); cache `database` para leitura GitHub com TTL curto.
- **Núcleo:** `GitHubClient` (HTTP via `Illuminate\Http\Client`, timeouts, `Http::fake` nos testes), `SyncRepositoriesAction`, `RecordWebhookEventAction`/`EvaluatePublicationAction`, `RunHealthChecksJob`/`HealthCheckAction`.
- **Sem integração operacional obrigatória na 7.2:** sincronização é leitura; webhook é ingestão; nenhuma escrita no GitHub (sem disparo de deploy).

## 3. Modelo de eventos e implantações (PD-701)

**Definição formal — não confundir evento com publicação:**

| Conceito | Significado | Concreção |
|---|---|---|
| **Evento de repositório** (`ship`/`push`/`release`) | Fato externo ocorrido no GitHub | Registro bruto, append-only, idempotente — **não** é publicação |
| **Implantação** (`deployment`) | Iniciativa concreta de implantar um SHA em um ambiente | Transição de estado; `pending` só aqui e só quando iniciada |
| **Publicação** (`website_publications`) | Implantação real reconhecida pelo WebStudio | Fonte da verdade; `result pendente/sucesso/falha/rollback`; até terminal pode transicionar, depois imutável |

**Regras:**
- `push`/`release` **nunca** criam publicação; apenas evento rastreável (`website_repository_events`).
- `deployment` cria **uma** publicação `result=pendente` por `github_deployment_id` (idempotência); sem `deployment`, sem `pending`.
- `deployment_status`: `success`→`sucesso`, `failure`/`error`→`falha`, `inactive`/`cancelled`→`falha` com nota (rollback vira `rollback` apenas por ação humana, nunca automática).
- Publicações manuais continuam `source=manual`; webhooks usam `source=webhook` e preenchem `github_deployment_id`/`commit_reference`.
- Todas as transições passam por Action transacional (`DB::transaction`, `lockForUpdate` no registro, `23505→no-op` em corrida de webhooks, padrão `CheckExpirationsAction`).

## 4. Fluxo de autenticação (PD-702)

1. OWNER cadastra `Credential` (`type=service_token`, label "GitHub — {repo}", `secret_encrypted`) via rota existente (`credentials.store`, `credentials.view`).
2. `GitHubClient` lê o segredo em memória, envia `Authorization: Bearer <token>` em chamadas API; o token **nunca** entra em Inertia props, JSON, logs, `metadata` ou mensagens de erro.
3. Formulários de edição nunca pré-preenchem segredo ("deixar em branco para não alterar", padrão vigente).
4. Migração futura para GitHub App: trocar apenas o `GitHubClient` (App JWT→installation token) sem mudar eventos/publicações (decisão D-032 preserva o caminho).

## 5. Idempotência de webhooks

- **Chave primária:** `X-GitHub-Delivery` (UUID do GitHub) em `unique index`; replay retorna **200** sem duplicar (mesma semântica do fingerprint de alertas).
- **Chave secundária:** `github_deployment_id` em `unique` para publicações derivadas de `deployment`.
- Em `23505` (corrida PG17): tratar como sucesso idempotente (`firstOrCreate` + verificação `created=false`), nunca duplicar nem falhar o webhook.
- `ping`/`push`/`release` geram só evento; `deployment`/`deployment_status` geram evento + publicação.

## 6. Segurança HMAC

- Segredo por repositório em `credentials` (`service_token`, webhooks). O segredo local do código é o mesmo segredo configurado no GitHub (campo `secret` do webhook).
- Verificação: `hash_hmac('sha256', rawBody, secret)` comparado a `X-Hub-Signature-256` com `hash_equals` (tempo constante); assinatura inválida → **401**, sem `500`, sem log de corpo/segredo.
- Endpoint fora do grupo `auth`, mas com `throttle` (padrão `throttle:5,1` do reveal) e sem cache (`Cache-Control: no-store`).
- Rotas: `POST /webhooks/github` (roteamento por `repository.full_name` para localizar o repositório; repositório desconhecido → **404** genérico, sem revelar inventário).

## 7. Estratégia de sincronização e cache

- `SyncRepositoriesJob` (`ShouldQueue`, `WithoutOverlapping`, `tries=3`, `timeout=120`) + botão manual "Sincronizar GitHub" (`websites.manage`).
- Leitura mínima: `GET /repos/{full_name}` + `GET /repos/{full_name}/commits?per_page=1&sha={default_branch}`; até 2 chamadas por repo por sincronização (respeito a rate limit).
- Cache local: `last_commit_sha`, `last_commit_at`, `last_synced_at` em `website_repositories` + `Cache::remember('github:repo:{id}', 600s)`. Falha de rede **não apaga** o cache (exibe "última sincronização" + erro).
- GitHub fora do ar ou PAT inválido → erro visível, sem exceção com token no relatório/log.

## 8. Health-check e prevenção de SSRF (PD-704)

- **Job diário 06:30 `America/Sao_Paulo`** (`Schedule::job(...)->dailyAt('06:30')->timezone(...)->withoutOverlapping()->runInBackground()`), segredo nunca logado.
- **Verificações:** GET/HEAD com `timeout=10s`; status HTTP; duração (`latency_ms`); certificado SSL: validade (`notAfter`), emissor, autoridade (via `openssl_x509_parse` em `stream_socket_client` + `verify_peer`); **corpo da resposta nunca armazenado** (apenas cabeçalhos de status).
- **Ignorar:** `websites.status=desativado`, ambientes `status != ativo`, ambientes sem `url` válida ou cujo domínio não resolve para host público.
- **Anti-SSRF (obrigatório):** apenas `http`/`https` com hostname público; resolver via DNS e **bloquear** `127.0.0.0/8`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `169.254.0.0/16`, `::1/128`, `fd00::/8`, `localhost`/`*.local`/`*.internal`; redirecionamentos manuais com revalidação em cada hop (máx. 3); falha de validação = `ok=false` com motivo `ssrf_blocked`/`dns_failed`, **sem** seguir o redirect proibido.
- **Registro:** `website_health_checks(website_id, environment_id, http_status, ok, latency_ms, ssl_expires_at, checked_at)` (append-only, sem SoftDelete); último resultado exibido no detalhe do site.

## 9. Permissões e políticas

| Permissão | OWNER | COMMERCIAL | DEVELOPER |
|---|---|---|---|
| `websites.view` / `hostings.view` | todos | escopado (oportunidade/demanda) | escopado (projeto acessível) |
| `websites.manage` (sync, CRUD ativos) | ✅ | ❌ | ❌ |
| `websites.publications` (registrar/confirma publicações) | ✅ | ❌ | ✅ projetos acessíveis |
| `credentials.view` (PAT/segredo webhook) | ✅ | ❌ | ❌ |
| Webhook `POST /webhooks/github` | — (sem sessão, HMAC) | — | — |

Nenhuma permissão existente removida; sidebar permanece só UX; rota sempre `can:` + Policy (`WebsitePolicy::view/manage/publish`).

## 10. Rotas e telas previstas

**Rotas (função, sem implementação nesta fase):**
| Método | URI | Acesso |
|---|---|---|
| `POST` | `/webhooks/github` | HMAC (sem `auth`) |
| `POST` | `/sites/{website}/repositories/{repository}/sync` | `websites.manage` |
| `POST` | `/sites/{website}/publications` (evolução) | `websites.publications` |
| `GET` | `/sites/{website}` (abas Publicações/Health) | `websites.view` |

**Telas (extensão de `/sites/{site}`, sem página principal nova):** aba Repositórios (botão Sincronizar + badge último commit), aba Publicações (fonte `manual`/`webhook`, checklist pré-publicação: produção ativa; domínio principal ativo; sem alerta crítico aberto; commit informado), aba Saúde (último `ok`/SSL/duração), shadcn/ui + Inertia, pt-BR.

## 11. Modelagem de dados proposta (sem migration nesta fase)

| Tabela | Colunas-chave | Índices/constraints |
|---|---|---|
| `website_repositories` (alteração) | `github_full_name`, `last_commit_sha`, `last_commit_at`, `last_synced_at` | `index github_full_name` |
| `website_repository_events` (nova) | `repository_id FK cascade`, `delivery_id unique`, `event_type push/release/deployment/deployment_status/ping`, `commit_sha`, `ref`, `payload_summary jsonb`, `signature_valid bool` | `unique delivery_id`; só `created_at` |
| `website_publications` (alteração) | `source manual/webhook`, `github_deployment_id unique nullable`, `result` += `pendente` | `unique github_deployment_id` parcial; `pendente` mutável até terminal |
| `website_health_checks` (nova) | `website_id`, `environment_id`, `http_status`, `ok`, `latency_ms`, `ssl_expires_at`, `checked_at` | `index (website_id, checked_at desc)`, append-only |

## 12. Estratégia de testes PostgreSQL 17 (Pest 4 / PHPUnit 12)

- **`Http::fake`** para sync (não chamar github.com nos testes); **fila síncrona** nos testes (padrão vigente).
- **Casos:** HMAC válido/inválido (401 sem log de secret), replay `delivery_id` (1 registro), `push` não cria publicação, `deployment` cria `pendente`, `deployment_status` transiciona, concorrência PG (`NOWAIT`/único em corrida, 1 publicação), SSRF (literal e hospedeiro com IP privado, redirect para interno bloqueado), saúde ignora desativado/sem URL, timeout=10s respeitado, corpo nunca persistido, permissões por perfil (401/403/200), sem regressão nos 115 testes + `typecheck`/`build` verdes.
- **`php artisan test` (skipped nunca conta como passed)**, execuções com overrides PG17 (container `webstudio-pgsql` porta 5433) como na Fase 6.3.

## 13. Critérios de aceite por subfase

| Subfase | Aceite |
|---|---|
| **7.1** (esta) | Decisões D-031–D-034 publicadas; `PHASE-7-DESIGN.md` revisado; nenhum conflito bloqueante; sem código |
| **7.2** | Repositório exibe último commit/branch após sync manual; `Cache` 600 s respeitado; falha preserva cache; testes PG verdes |
| **7.3** | Webhook assinado ingestão idempotente; `push`→só evento, `deployment`→`pendente`, `status`→terminal; HMAC inválido 401; concorrência 1 registro |
| **7.4** | Checklist bloqueia publicação inválida; health 06:30 grava evidências sem corpo; SSRF bloqueado em todos os casos; desativados ignorados |
| **7.5** | Suíte completa PG17 + `typecheck` + `build` verdes; docs `DATABASE/SECURITY/DECISIONS` alinhados |

> **Fechamento da Fase 7 (7.5):** 190 testes PG17.11 verdes, `typecheck`/`build` verdes, docs alinhados (DATABASE §1.1 + SECURITY §7 + D-037–D-039). Ressalva 7.4 encerrada por `SiteHealthRunIdsTest` (2 domínios → 2 históricos, ids distintos sequenciais). Deploy automático pelos providers não implementado (D-033); Fase 8 bloqueada.

## 14. Riscos

| # | Impacto | Mitigação |
|---|---|---|
| 1 | Token vazado (Inertia/log/exceção) | `encrypted` + `hidden` + teste de ausência em resposta/log |
| 2 | Replay falsifica publicação | `unique delivery_id` + `hash_equals` + `throttle` |
| 3 | Rate limit GitHub quebra sync | Sync manual + cache + backoff + `tries=3` |
| 4 | SSRF via URL de ambiente controlada | Allowlist de scheme + bloqueio de faixas + redirect validado |
| 5 | `pending` eterno sem resultado | Dashboard evidencia pendentes >24 h; nenhum fechamento automático (decisão humana) |
| 6 | Escopo vaza entre clientes | Reuso de `WebsitePolicy::view` + testes por perfil |

---

*Fim de `docs/PHASE-7-DESIGN.md` — Fase 7 concluída (7.5).*
