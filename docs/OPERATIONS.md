# OPERATIONS — WebStudio OS

**Versão:** 1.0 — Fase 0  
**Fonte:** Doc.md v1.0 §9

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
| Aplicação web | Laravel 13 (PHP 8.3/8.4) + React build estático servido pelo Laravel | `PIPELINE: Vite build → public/build` |
| Banco | PostgreSQL gerenciado (Render Postgres, Neon, Supabase, Hetzner...) | **[PENDENTE decisão final]** |
| Storage | S3-compatível (AWS S3, Backblaze B2, Cloudflare R2, MinIO) via disco `s3` | **[PENDENTE fornecedor]** |
| Filas | Worker Laravel (`php artisan queue:work`) — driver `database` ou `redis` | **[PENDENTE driver]** |
| Scheduler | `schedule:run` via cron a cada minuto | Obrigatório (notificações, prazos, backups) |
| Cache | `database`/`file` inicialmente; `redis` se disponível | **[PENDENTE]** |
| HTTPS | Certificado Let's Encrypt (automático na hospedagem) | Obrigatório |
| Domínio | Domínio próprio + DNS | **[PENDENTE domínio]** |
| Logs | `LOG_CHANNEL=stack` + driver de log do provedor | — |
| Backups | Snapshot diário do PG + `storage` sync (rclone/s3 sync) | Obrigatório |
| Monitoramento | Uptime (BetterStack/UptimeRobot), erro Sentry/Flare [PENDENTE] | Recomendado |

### Hospedagem — decisão pendente
Critérios (Doc.md §9.3): compatibilidade PHP 8.3+, PostgreSQL gerenciado, S3, HTTPS, domínio próprio, backups, persistência, suporte a filas/scheduler, custo, suporte. Avaliar **Render** (recomendado inicial no Doc.md), **Fly.io**, **Hetzner Cloud** (mais custo-benefício, exige DevOps manual). Não decidir nesta fase; registrar em `DECISIONS.md`.

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

- [PENDENTE] Hospedagem final (Render vs alternativa).
- [PENDENTE] Domínio final e provedor de e-mail (transacional).
- [PENDENTE] Driver de filas/cache (database vs Redis).
- [PENDENTE] Fornecedor S3-compatível (R2/B2/AWS).
- [PENDENTE] Serviço de monitoramento de erros (Sentry/Flare).
- [PENDENTE] Política de retenção de backups e logs (LGPD).
