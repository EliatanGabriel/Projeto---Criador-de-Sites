# DEVELOPMENT — WebStudio OS

**Versão:** 1.0 — Fase 0  
**Fonte:** Doc.md v1.0 §11, §12

## 1. Princípios de Trabalho (Doc.md §11.1)

Tarefas pequenas e específicas. Não construir módulos inteiros sem análise prévia. Ciclo obrigatório: Analisar → Planejar → Implementar → Testar → Revisar → Documentar → Aprovar → Avançar.

## 2. Padrão de Prompts (Doc.md §11.3)

Toda solicitação ao OpenCode deve conter:

- **Contexto:** informações sobre o projeto.
- **Objetivo:** o que deverá ser desenvolvido.
- **Escopo:** funcionalidades incluídas.
- **Regras:** limitações e padrões obrigatórios.
- **Integração:** como a implementação se relaciona com os módulos existentes.
- **Testes:** o que deverá ser validado.
- **Critérios de aceite:** como saberemos que está concluído.
- **Relatório final:** arquivos criados, modificados, testes executados e pendências.

## 3. Controle de Versão (Doc.md §11.4)

- Git + GitHub. Branches por funcionalidade (`feat/*`, `fix/*`, `chore/*`, `docs/*`). Nunca desenvolver direto na `main` protegida.
- Commits pequenos e descritivos (ex.: `feat(demands): add ConvertDemandToProjectAction with transaction`).
- Revisar alterações antes de mesclar (PR + CI verde + aprovação).
- Não versionar `.env`. Não expor credenciais. `.env.example` versionado.
- Executar testes/lint antes de integrar.

## 4. Ambiente de Desenvolvimento (proposta)

### Pré-requisitos

- Git, PHP 8.3+ (recomendado 8.4), Composer 2, Node 20+ LTS (`node -v`), npm, PostgreSQL 16/17 (ou Docker).
- Extensões PHP: `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `dom`, `pcntl`, `redis` (se usar Redis), `zip`.
- Servidor web local: `php artisan serve` ou Laravel Herd (Windows/macOS) ou Sail (Docker) [PENDENTE decisão].

### Setup inicial (Fase 1 — não executar nesta etapa)

```bash
composer create-project laravel/laravel webstudio-os
composer require inertiajs/inertia-laravel:^3.0
npm install react@^19 react-dom@^19 @inertiajs/react@^3.0 typescript@^5.9 vite tailwindcss
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

## 5. Padrões de Código

### Backend (Laravel 13, PHP)

- Controllers finos; Actions/Services contêm regras. Exemplo: `app/Domain/Demands/Actions/ConvertDemandToProjectAction.php`.
- FormRequests com mensagens em pt-BR. Policies para authz. Observers/Events para auditoria e notificações.
- Enums PHP para status/prioridades. DTOs para dados de entrada complexos (briefing, conversão).
- `DB::transaction()` em operações críticas. Queries complexas em Queries/Domain Services, não em Blade/React.
- Estilo: Laravel Pint (`./vendor/bin/pint --test`), PSR-12. Sem comentários excessivos (código legível).
- Tipagem estrita PHP (return types, param types, `declare(strict_types=1)` onde aplicável).

### Frontend (React 19, TypeScript, Inertia v3)

- Pages em `resources/js/Pages` (Recursos: `Index`, `Create`, `Edit`, `Show`). Componentes reutilizáveis em `resources/js/Components/ui`.
- Layouts: `AuthenticatedLayout` (menu lateral fixo, topbar) e `GuestLayout` (login).
- Tipagem: `types/` gerados manualmente ou via `spatie/laravel-typescript-transformer` [PENDENTE]. Evitar `any`.
- `useForm` do Inertia para formulários; validar erros exibidos em pt-BR.
- Formatação: `Intl.DateTimeFormat('pt-BR', {dateStyle:'short'})` e `Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'})`. Centralizar em `lib/format.ts`.
- Estilização: Tailwind CSS (v4 [PENDENTE]). Wrapper de biblioteca de componentes escolhida.
- Estado global mínimo; usar props Inertia (`shared props`: `auth.user`, `flash`, `errors`). Evitar Redux/Zustand salvo necessidade comprovada.

## 6. Estratégia de Testes (proposta)

### Ferramentas

- Backend: Pest (recomendado, sintaxe moderna sobre PHPUnit 12/13 compatível com Laravel 13) **ou** PHPUnit [a decidir]. Cobertura com `pest --coverage` (meta: 80% nas Actions críticas).
- Frontend: TypeScript strict + ESLint + Prettier. Testes de componentes apenas para fluxos críticos com Vitest + Testing Library [PENDENTE decisão]. **Não** testar tudo.
- CI: GitHub Actions executa `composer test`, `npm run typecheck`, `npm run lint`, `npm run test` (se houver).

### Pirâmide de testes

1. **Unit (domínio):** Actions/Services isolados (ex.: cálculo de financeiro, detecção de duplicidade de cliente, geração de tarefas a partir de template). Sem banco, mock de Models onde necessário.
2. **Feature (HTTP + regras):** rotas Inertia, Policies, fluxos completos. Obrigatório para:
   - Conversão demanda→projeto (sucesso + **falha transacional com rollback** + **idempotência** — segunda conversão não cria segundo projeto).
   - Policies por perfil (OWNER, COMERCIAL, DESENVOLVEDOR) — positivo e negativo.
   - Fluxos de status (demanda: rascunho→enviada→em análise→aprovada→convertida; projeto: planejamento→concluído).
   - Financeiro: receita `prevista` não conta como `recebida`; despesa só conta quando `paga`.
   - Uploads: extensão/tamanho inválidos rejeitados.
3. **Browser (opcional):** Playwright para fluxo crítico (login → criar cliente → criar demanda → aprovar → converter → concluir tarefa) [PENDENTE se vale o custo na Fase 1].
4. **Frontend unit:** apenas para componentes críticos (ex.: tabela paginada, formulário de demanda, dashboard cards) se Vitest aprovado.

### Dados de teste

- Usar Factories (`ProjectFactory`, `DemandFactory`, `UserFactory`, `ClientFactory`) + `RefreshDatabase` ou `LazilyRefreshDatabase`.
- Nunca usar dados de produção nos testes. Sem dados reais em CI.
- Seeders de teste mínimos (papéis/perfis) via `DatabaseSeeder` apenas para dev local.

## 7. Qualidade (Doc.md §12)

Funcionalidade ponta a ponta, segurança com auth/authz no backend, integridade (transações), manutenibilidade (código modular), testabilidade (testes automatizados para regras/permissões/operações críticas), responsividade (desktop/tablet/mobile), desempenho (paginação, índices, queries eficientes), confiabilidade (backups, logs, recuperação).

## 8. Checklist de PR

- [ ] Título e descrição claros (contexto, o que muda, como testar).
- [ ] Testes adicionados/atualizados e passando.
- [ ] Lint/typecheck passando (`pint --test`, `npm run lint`, `tsc --noEmit`).
- [ ] Migrações reversíveis (usar `down()` correto, sem `dropIfExists` destrutivo em prod sem backup).
- [ ] Sem segredos expostos; `.env.example` atualizado se novas vars.
- [ ] Documentação atualizada se mudança arquitetural (`DECISIONS.md` se necessário).

## 9. Pendências de Desenvolvimento

- [PENDENTE] Ambiente local: Herd vs Sail vs nativo (definir na Fase 1).
- [PENDENTE] Biblioteca de componentes React e biblioteca de gráficos.
- [PENDENTE] Pest vs PHPUnit; Vitest/Testing Library vs sem testes FE.
- [PENDENTE] Form library (usar `useForm` nativo vs React Hook Form + Zod).
- [PENDENTE] Transpilação TS (Vite nativo vs esbuild/swc).
- [PENDENTE] Gerador de tipos TS a partir do backend.
