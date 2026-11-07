# OPERATIONS — WebStudio OS

**Versão:** 1.2 — Deploy Docker corrigido  
**Fonte:** Doc.md v1.0 §9, ARCHITECTURE.md §2

## 1. Ambientes

### Desenvolvimento (dev)
- Utilizado por Eliatan e Roger.
- PHP 8.3+, Composer 2, Node 20+ LTS, PostgreSQL local (ou Docker).
- `.env` local com banco `db=webstudio_os`, mailbox fake (Mailpit/Mailhog), disco S3 fake (MinIO) ou `local`.
- `npm run dev` (Vite) + `php artisan serve` (ou Herd/Sail).

### Homologação (staging)
- Validar funcionalidades antes de produção.
- URL própria (ex.: `staging.webstudio-os.com`), dados de teste (nunca dados reais de produção sem autorização).
- Deploy automático via CI após merge em `develop` ou tag (ver §4).

### Produção
- Operação diária da equipe (Eliatan, Alisson, Roger).
- Domínio próprio, HTTPS obrigatório, backups diários.
- `APP_DEBUG=false`, logs centralizados, monitoramento básico (uptime, erros, filas).

## 2. Infraestrutura Inicial

| Componente | Tecnologia / Provedor | Status |
|---|---|---|
| Aplicação web | Laravel 13 (PHP 8.4) + React build estático servido pelo nginx | `Dockerfile` multi-stage |
| Banco | PostgreSQL 17 gerenciado (Supabase) | Confirmado |
| Storage | S3-compatível via disco `s3` (provider a definir, P-016) | provider pendente; MVP local ok |
| Filas | Worker Laravel `database` (sem Redis) | Confirmado |
| Scheduler | `schedule:run` via cron a cada minuto | Cron Render (1 min) |
| Cache | `database` | Confirmado |
| HTTPS | Certificado Let's Encrypt (automático na hospedagem) | Obrigatório |
| Domínio | Domínio próprio + DNS | **[PENDENTE domínio]** |
| Logs | `storage/logs` ou provedor | — |
| Backups | Snapshot diário do PG + `storage` sync (rclone/s3 sync) | Obrigatório |
| Monitoramento | Uptime (BetterStack/UptimeRobot), erro Sentry/Flare [PENDENTE] | Recomendado |

### Hospedagem — Render + Supabase (D-041)

- **Web Service**: Render serviço Docker usa o `Dockerfile` do repositório; Build não roda migrations; variáveis `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL`, `SESSION_SECURE_COOKIE=true`, `DB_*` (Supabase) e demais segredos configurados via Render **env vars** (sem secrets no Docker).
- **Worker**: `php artisan queue:work database --sleep=3 --tries=3 --timeout=120` (filas `database`).
- **Cron**: `php artisan schedule:run` a cada minuto (funcionalidade agendada diária 06:30/07:00 + mensal 05:00).
- **Health-check**: endpoint `/up` nativo Laravel (healthCheckPath no `render.yaml`).
- **Migrations**: nunca no build; via **Pre-Deploy Command** (se habilitado no painel) ou Job manual controlado (`php artisan migrate --force`); se falhar, a publicação não segue. Rollback: voltar ao build anterior do Render, **sem** `migrate:rollback` destrutivo.

## 3. Estratégia de Deploy

- GitHub como origem. Branch `main` protegida (requer PR + CI verde + 1 aprovação).
- Branches por funcionalidade: `feat/*`, `fix/*`, `chore/*`, `docs/*`.
- CI (GitHub Actions): `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan test` (ou pest), `php artisan migrate --force`, `php artisan config:cache`, `route:cache`, `view:cache`.
- Deploy automático após aprovação em `main` (Render Deploy Hook, Fly, etc.). Migrações controladas em `deploy` step.
- Variáveis de ambiente separadas por ambiente (dev/staging/prod).
- Procedimento de rollback: manter build anterior; se falha, `php artisan migrate:rollback --step=1` + rollback do deploy do provedor. Testar rollback em staging antes de produção.

## 4. Estratégia de Publicação (fluxo)

1. Dev em branch de funcionalidade.
2. CI roda testes/lint/tipos.
3. PR → review (Eliatan e/ou Roger) → merge em `develop` (staging).
4. Validação em staging.
5. Merge `develop` → `main` → deploy automático em produção.
6. Pós-deploy: verificar logs, filas, scheduler, backup.

## 5. Backups e Recuperação

- PostgreSQL: backup diário automático do provedor + retenção 7 dias [PENDENTE ajustar].
- Arquivos S3: versionamento habilitado no bucket + backup semanal.
- Procedimento de restore: restaurar dump em novo banco, validar contagem de registros, trocar `DATABASE_URL`, testar login.
- Nunca testar com dados reais de produção em ambiente sem proteção.

## 6. Observabilidade

- Logs da aplicação (`storage/logs` ou provedor).
- Filas: monitorar `failed_jobs`, alertar se > 0 por mais de 1h.
- Scheduler: log de `schedule:run` para garantir cron ativo.
- Métricas de negócio: Dashboard do próprio sistema (indicadores reais, sem dados fictícios).

## 7. Segurança Operacional

- HTTPS obrigatório; headers de segurança (CSP, HSTS, X-Frame-Options) via middleware ou provedor.
- `.env` nunca versionado; segredos apenas no provedor.
- Acesso à infra restrito aos 3 integrantes (MFA no GitHub/provedor recomendado).
- Revisão trimestral de acessos, logs e backups.

## 8. Riscos Operacionais

| Risco | Impacto | Mitigação |
|---|---|---|
| Hospedagem sem filas/scheduler persistentes | Notificações e prazos não disparam | Exigir esses recursos na escolha da hospedagem; usar `database` driver como fallback |
| Laravel 13 muito novo + pacotes incompatíveis | Deploy quebra | Travar versões com `^13.0` apenas onde verificado; rodar CI completo antes do deploy |
| Sem backup testado | Perda de dados | Teste de restore mensal em staging |
| Dados fictícios em produção | Contaminação de métricas/financeiro | Proibido por AGENTS.md; seeders apenas em dev/staging |
| Custo crescente de S3/Redis/PG | Margem | Estimar 3 usuários + uploads moderados; começar com plano gratuito/custo baixo e monitorar |

## 9. Pendências de Operações

- [PENDENTE] Domínio final e provedor de e-mail (transacional).
- [PENDENTE] Driver de filas/cache (database vs Redis).
- [PENDENTE] Fornecedor S3-compatível (R2/B2/AWS).
- [PENDENTE] Serviço de monitoramento de erros (Sentry/Flare).
- [PENDENTE] Política de retenção de backups e logs (LGPD).

## 10. Configuração dos Processos Render

### Web Service
Executa a aplicação Laravel e atende requisições HTTP.
- Uses the corrected multi-stage Dockerfile.
- Env vars: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL`, `SESSION_SECURE_COOKIE=true`, `DB_*`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`.
- Health-check: `/up` endpoint.
- Disk mount: `webstudio-storage` mounted at `/app/storage/app/private`.

### Background Worker
Comando: `php artisan queue:work database --sleep=3 --tries=3 --timeout=120`
- Usa a mesma imagem Docker do Web Service.
- Variáveis de ambiente necessárias: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `QUEUE_CONNECTION=CACHE_STORE=SESSION_DRIVER=database`.
- Deve ser configurado no painel Render como tipo "Worker" com o `startCommand` acima.
- Não requer Redis - usa driver `database` já configurado.

### Cron Job
Comando: `php artisan schedule:run`
- Frequência: a cada minuto (`schedule: "* * * * *"`).
- Usa a mesma imagem Docker do Web Service e variáveis de ambiente compartilhadas.
- Configurado no painel Render como tipo "Cron" com schedule `* * * * *`.

## 11. Variáveis de Ambiente Necessárias

### Obrigatórias (Web Service):
- `APP_NAME` - "WebStudio OS"
- `APP_ENV` - "production"
- `APP_DEBUG` - "false"
- `APP_KEY` - base64 key (fornecida via Render env vars ou gerada na primeira inicialização)
- `APP_URL` - "render.com" ou domínio próprio
- `SESSION_SECURE_COOKIE` - "true"
- `DB_CONNECTION` - "pgsql"
- `DB_HOST` - hostname do Supabase
- `DB_PORT` - "5432"
- `DB_DATABASE` - "webstudio_os"
- `DB_USERNAME` - "webstudio"
- `DB_PASSWORD` - (fromSecret no Render)
- `QUEUE_CONNECTION` - "database"
- `CACHE_STORE` - "database"
- `SESSION_DRIVER` - "database"
- `SESSION_LIFETIME` - "120"
- `PORT` - "8000" (ou porta definida pelo Render)

### Opcional/Secreto:
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_DEFAULT_REGION` - para disco S3
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` - para e-mails
- `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` - se mudar para Redis futuramente

## 12. Processo de Migração de Produção

Migrations **nunca** devem ser executadas durante `docker build`. O procedimento correto:

1. **Pre-Deploy Command (Render)**: Se o plano Render suportar, configurar command `php artisan migrate --force` como Pre-Deploy Command. Isso executa antes da nova versão ser liberada.
2. **Se Pre-Deploy não estiver disponível**: executar migrações via Job manual controlado.
   - Criar um Job `RunMigrations` que executa `php artisan migrate --force`.
   - Disparar o job após o deploy confirmação.
3. **Não rodar simultaneamente em múltiplas instâncias**: 
   - Apenas uma instância deve rodar migrations ao mesmo tempo.
   - Usar `php artisan migrate --force` com lock ou garantir que apenas o Web Service recém-deployed rode as migrations.
4. **Não rodar rollback destrutivo automaticamente**: 
   - Rollback manual apenas via `php artisan migrate:rollback --step=1` operado por OWNER/Admin.
   - Nunca `migrate:rollback` em produção automaticamente.
5. **Fluxo**:
   - Novo build subado no Render.
   - Se houver Pre-Deploy Command, ele roda `migrate --force`.
   - Se falhar, deploy é interrompido e build anterior mantido.
   - Se sucesso, nova versão liberada.
   - Pós-deploy: verificar se migrations aplicadas corretamente.

## 13. Recuperação e Rollback

- **Rollback por build**: Manter o build anterior no Render. Caso a nova versão apresente problemas, selecionar o build anterior no painel Render e promover para produção.
- **Rollback de migrations**: Nunca use `migrate:rollback` em produção. Se for necessário reverter mudanças de schema, planejar uma migration de correção ou usar `migrate:rollback --step=1` apenas em staging, com aprovação OWNER.
- **Recuperação após falha**:
  1. Verificar logs em `storage/logs` ou painel Render.
  2. Se o container não iniciar, verificar variáveis de ambiente e permissões.
  3. Promover build anterior via painel Render.
  4. Testar health-check em `/up`.
- **Health-check**: Endpoint `/up` deve retornar 200 quando a aplicação estiver saudável. Configurado no `render.yaml` como `healthCheckPath: /up`.

## 14. Deploy Procedure

1. Push em `main` dispara GitHub Actions CI.
2. CI roda: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan test`.
3. CI publica no Render (ou Render faz auto-deploy após push em `main`).
4. Se houver Pre-Deploy Command, migrations são executadas.
5. Novo container sobe com `APP_ENV=production`, `APP_DEBUG=false`.
6. Pós-deploy: verificar `/up`, filas, scheduler, logs.
7. Se houver falha, promover build anterior e rodar `php artisan migrate:rollback --step=1` se necessário.