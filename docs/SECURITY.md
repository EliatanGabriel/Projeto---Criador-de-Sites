# SECURITY — WebStudio OS

**Versão:** 1.4 — Fase 5
**Fonte:** Doc.md v1.0 §1.3, §5 Módulo 01, §7

## 1.1 Permissões Fase 2

`users.view/create/edit/delete` (OWNER), `clients.view/create/edit/delete` (OWNER completo, COMMERCIAL view/create/edit), `commercial.view/create/edit` (OWNER + COMMERCIAL). `demands.view/create/edit/send/analyze/approve/convert` (convert OWNER). `projects.view/create/edit` (OWNER gerencia, COMMERCIAL view de próprios, DEVELOPER view de projetos com acesso), `tasks.view/create/edit/assign` (OWNER total, DEVELOPER view/edit de próprias, sem assign). `templates.view` (OWNER + DEVELOPER leitura), `templates.manage` (OWNER). Verificação em middleware `can:` + Policies + FormRequests + Actions transacionais. `is_active` bloqueia login e invalida sessão em andamento via middleware `EnsureUserIsActive`. Último OWNER ativo protegido (422). Anexos em disco privado `local`, download via rota autenticada + policy, sem URL pública, whitelists MIME, 10 MB. Notificações `database` (sem e-mail), link de recurso, controle de dono. Templates inativos não selecionáveis; sem exclusão destrutiva (softDeletes + inativação).

## 1. Princípios

- Sem registro público. Cadastro de usuários apenas por administrador autorizado (OWNER/Admin).
- Esconder botões na interface **não** é controle de permissão. Toda operação é validada no servidor (Policies/Gates + FormRequests).
- Menor privilégio: cada perfil acessa apenas o necessário.
- Sem acesso externo (clientes/portal público) na primeira versão.

## 2. Perfis e Permissões (Doc.md §7.1)

### OWNER / Administrador (Eliatan)

Acesso completo: gerenciar usuários, clientes, demandas (incluindo aprovar), projetos, distribuir tarefas, gerenciar templates, consultar financeiro e relatórios, configurar plataforma.

### COMERCIAL (Alisson)

Permitido: cadastrar/consultar clientes, gerenciar oportunidades, criar propostas, criar demandas, editar demandas **não enviadas**, enviar demandas, consultar situação de projetos e infos comerciais autorizadas.

Restrições: **não** aprovar demandas, **não** alterar configurações técnicas, **não** administrar usuários, **não** modificar tarefas técnicas sem autorização, **não** acessar configurações financeiras restritas.

### DESENVOLVEDOR (Roger)

Permitido: consultar projetos atribuídos, briefings, arquivos necessários, visualizar tarefas, atualizar tarefas atribuídas, adicionar comentários, anexar arquivos, registrar bloqueios, consultar infos técnicas.

Restrições: **não** aprovar demandas, **não** alterar negociações comerciais, **não** administrar usuários, **não** alterar valores contratados, **não** acessar informações financeiras restritas.

### Implementação

- Laravel Auth (sessão) + Breeze/Fortify **[PENDENTE]**.
- Policies por model (`DemandPolicy`, `ProjectPolicy`, `TaskPolicy`, `FinancePolicy`, `UserPolicy`) + Gates para ações globais.
- Middleware `auth`, `verified` (se habilitado), `throttle:login` (rate limiting).
- Teste obrigatório: cada policy deve ter Feature test cobrindo OWNER/COMERCIAL/DESENVOLVEDOR (positivo e negativo).
- Um usuário pode acumular responsabilidades (múltiplas roles/permissões).

## 3. Autenticação

- Login por e-mail/senha, logout, recuperação de senha (e-mail), alteração de senha.
- Gerenciamento de usuários: criação, ativação/desativação (`is_active` ou `disabled_at`), controle de sessões (listar/revogar outras sessões se Fortify), registro de último acesso (`last_login_at`).
- Senhas com hashing seguro (bcrypt default Laravel; argon2 opcional via `Hash::driver`).
- Rate limiting no login (ex.: `throttle:5,1` ou Fortify limiter em `AppServiceProvider`).
- Sessões: `http_only`, `same_site=lax`, `secure` em produção, `SESSION_DRIVER=database` ou `cookie` [a decidir], expiração `SESSION_LIFETIME` (ex.: 120 min).
- CSRF: proteção nativa Laravel (`VerifyCsrfToken`) obrigatória em todas as rotas web/Inertia.
- 2FA **[PENDENTE — recomendar Fortify se desejar TOTP]**.

## 4. Autorização — Obrigatória no Backend

- FormRequests validam entrada (tipos, tamanhos, formatos, enums, unicidades).
- Policies autorizam CRUD por perfil e por estado (ex.: Comercial só edita demanda em `rascunho`; Dev só edita tarefa atribuída a ele).
- Rotas web protegidas com `auth` + `can:` ou `authorizeResource`.
- Sem API pública no MVP; se criar `api.php` para webhooks, usar Sanctum tokens com escopo mínimo.

## 5. Uploads e Arquivos (Doc.md §7.2)

- Disco `s3` (armazenamento compatível com S3) para produção; `local` apenas em dev com mesma interface.
- Validação obrigatória: extensão/MIME (`mimes:pdf,jpg,png,webp,zip...`), tamanho máximo (`max:10240` ex.: 10 MB), `image` vs `file` quando aplicável, scan básico de MIME real (`Fileinfo`).
- URLs privadas para arquivos confidenciais: `Storage::temporaryUrl()` (assinadas, expiração curta). URLs públicas apenas para arquivos explicitamente públicos.
- Nomes de arquivos aleatórios (UUID/hash) no storage; nome original preservado apenas em metadados (`uploaded_files`).
- Limite global por usuário/demanda/projeto [PENDENTE configurar em Settings].

## 6. Segredos e Ambiente

- Variáveis sensíveis apenas em `.env` (nunca versionar, nunca commitar). Fornecer `.env.example` com chaves sem valores.
- `APP_KEY`, `DB_PASSWORD`, `AWS_*`, `MAIL_*`, tokens GitHub — gerenciados por secret manager da hospedagem (Render env vars).
- HTTPS obrigatório em produção (HSTS se hospedagem suportar).
- `APP_DEBUG=false` em produção (logs via `LOG_CHANNEL` estruturado).

## 7. Logs e Auditoria

- `activity_logs` + `demand_status_history` + `project_activities` para ações importantes (login, aprovação, conversão, alteração de valores, exports).
- Log de acessos negados (Policy denied) para monitoramento.
- Backups periódicos (banco + arquivos) — ver OPERATIONS.md.
- `XSS`: React escapa por padrão; nunca usar `dangerouslySetInnerHTML` com dados de usuário sem sanitização.
- `Mass assignment`: `$fillable` estrito ou `$guarded = []` proibido nos Models.
- `SQL injection`: usar Eloquent/Query Builder; proibir concatenação raw em queries (apenas `DB::raw` com binds).

## 8. Testes de Segurança (Fase 2+)

- Feature tests de policies (cada perfil x cada ação crítica).
- Teste de rate limiting no login.
- Teste de validação de upload (extensão/tamanho).
- Teste de CSRF em rotas críticas (Laravel cobre por padrão, mas registrar).
- Auditoria manual de dependências (`composer audit`, `npm audit`) no CI.

## 9. Pendências de Segurança

- [PENDENTE] Breeze vs Fortify (decidir se precisa 2FA, tokens API).
- [PENDENTE] Spatie Permission vs nativo.
- [PENDENTE] 2FA obrigatório para OWNER?
- [PENDENTE] Política de senha (tamanho mínimo, complexidade, expiração) — definir com Eliatan.
- [PENDENTE] Retenção de logs/atividades (LGPD).
- [PENDENTE] Gestão de backups e restauração testada.
