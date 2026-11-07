# AGENTS.md — WebStudio OS

**Versão:** 1.0  
**Baseado em:** Doc.md v1.0 — Documento Mestre  
**Status:** Fase 0 — Planejamento. Nenhuma funcionalidade implementada.

## 1. Identidade do Projeto

- **Nome:** WebStudio OS
- **Tipo:** Sistema interno de gestão empresarial e produção de sites (plataforma web privada cloud)
- **Público inicial:** equipe interna de 3 pessoas — Eliatan (gestão técnica/arquitetura), Alisson (comercial), Roger (desenvolvimento)
- **Idioma:** Português brasileiro (pt-BR) — interface, datas, moeda (BRL), mensagens
- **Objetivo inicial:** centralizar clientes, demandas, projetos e tarefas. MVP **não** gera sites automaticamente.
- **Objetivo futuro:** SaaS de criação/gerenciamento de sites (Fora do MVP)

## 2. Documentação Obrigatória

Antes de qualquer tarefa, ler nesta ordem:

1. `AGENTS.md` (este arquivo)
2. `Doc.md` — documento mestre, fonte da verdade
3. `docs/PRD.md`
4. `docs/ARCHITECTURE.md` e `docs/DATABASE.md` para tarefas técnicas
5. `docs/SECURITY.md` para auth/permissões/uploads
6. `docs/DEVELOPMENT.md` e `docs/OPERATIONS.md` para fluxo e infra
7. `docs/DECISIONS.md` — registrar qualquer desvio ou nova decisão

Regra: **não inventar decisões não aprovadas.** Decisões pendentes devem ser marcadas como `[PENDENTE]` e registradas em `DECISIONS.md` após aprovação.

## 3. Stack Aprovada (Doc.md §4.1)

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 |
| Frontend | React |
| Integração | Inertia.js |
| Linguagens | PHP / TypeScript |
| Banco | PostgreSQL |
| Estilização | Tailwind CSS |
| Componentes | Biblioteca React compatível **[PENDENTE - a definir na Fase 1]** |
| Autenticação | Laravel (Breeze/Fortify — **[PENDENTE]**) |
| Autorização | Policies + permissões **[PENDENTE: Spatie Permission vs nativa]** |
| Arquivos | S3-compatível (disco `s3`) |
| Versionamento | Git + GitHub |
| Dev | OpenCode |
| Hospedagem | Cloud — Render ou equivalente **[PENDENTE decisão final]** |

Versões devem ser confirmadas no início da Fase 1 (ver `ARCHITECTURE.md §2`).

## 4. Arquitetura

- Monólito modular, **sem microsserviços** no MVP.
- Domínios aprovados: Authentication, Users, Clients, Commercial, Demands, Projects, Tasks, Briefings, Templates, Websites, Finance, Notifications, Settings, Reports.
- Evitar controllers gigantes. Usar Services/Actions para operações críticas (ex.: conversão demanda→projeto).
- Inertia.js: sem API REST separada no MVP, salvo necessidade comprovada (webhooks/integrações externas).

## 5. Fluxo Operacional Inviolável (Doc.md §3)

Comercial → Cliente → Serviço → Briefing → Anexos → Info comercial → Demanda enviada → Análise técnica → [complementar | aprovar] → **conversão transacional demanda→projeto** → tarefas por template → responsáveis/prioridades → execução → revisão → preparação → entrega → concluído/manutenção.

**Regra central:** nenhuma demanda entra em desenvolvimento sem passar pela análise técnica (Eliatan).

## 6. Permissões (resumo — detalhe em SECURITY.md)

- **OWNER/Admin (Eliatan):** acesso total, ou seja, CRUD usuários/clientes/demandas/projetos/templates, aprovar demandas, financeiro, relatórios, configurações.
- **Comercial (Alisson):** clientes, leads/propostas, demandas (criar/editar rascunho, enviar). **Não** aprova demandas, não administra usuários, não acessa financeiro restrito.
- **Dev (Roger):** projetos/tarefas atribuídos, briefings, comentários, anexos, bloqueios. **Não** aprova demandas, não altera negociação/valores, não acessa financeiro restrito.
- Esconder UI não é autorização. Toda regra é validada no backend (Policies/Gates + FormRequests).

## 7. Regras de Desenvolvimento

- Seguir ciclo: Analisar → Planejar → Implementar → Testar → Revisar → Documentar → Aprovar → Avançar.
- Tarefas pequenas e específicas. Não construir módulos inteiros sem análise.
- Não modificar módulos não relacionados sem justificativa.
- Não remover funcionalidades para resolver problema pontual.
- Não criar dados fictícios em produção.
- Não versionar `.env`. Não expor credenciais.
- Commits pequenos, em branches por funcionalidade, nunca direto na `main` protegida.
- Rodar testes/lint antes de merge (ver `DEVELOPMENT.md`).

## 8. Estrutura de Repositório (proposta Fase 1)

```
app/
  Domain/{Authentication, Users, Clients, Commercial, Demands, Projects, Tasks, Briefings, Templates, Websites, Finance, Notifications, Settings, Reports}/
    Actions/ Services/ DTOs/ Policies/ Observers/
  Http/{Controllers, Requests, Resources}
  Models/ Notifications/ Jobs/ Mail/
resources/js/
  Pages/{Dashboard, Clients, Demands, Projects, Tasks, Finance, ...}/
  Layouts/ Components/ Hooks/ lib/
routes/web.php  (Inertia, sem API REST no MVP)
database/migrations/ seeders/
tests/{Feature, Unit, Browser?}
docs/
.env.example  (nunca commitar .env real)
```

Detalhe em `ARCHITECTURE.md §5` e `DEVELOPMENT.md`.

## 9. Banco de Dados

- PostgreSQL relacional, FKs, índices, integridade referencial, soft deletes onde aplicável, sem hard delete em financeiro/auditoria.
- Tabelas aprovadas em `Doc.md §6` (detalhadas em `DATABASE.md`).
- UUID vs bigint **[PENDENTE — recomendar UUID v7 em DECISIONS.md para preparação SaaS]**.
- Transações obrigatórias em conversões, geração de tarefas, movimentos financeiros.
- Separação lógica entre dados comerciais/projetos/usuários preparando futura multi-tenancy (sem multi-tenancy no MVP).

## 10. Qualidade e Testes

- Testes automatizados para regras de negócio, permissões e operações críticas (Pest/PHPUnit Feature + Unit).
- Cobertura mínima exigida: conversão demanda→projeto (inclusive falha transacional), policies por perfil, fluxos de status, integridade financeira (nunca confundir contratado com recebido).
- Frontend: testes de componentes apenas para fluxos críticos **[PENDENTE: Vitest/Testing Library]** + validação TypeScript estrita.

## 11. O que NÃO Fazer Nesta Etapa

- Não implementar módulos/funcionalidades de negócio.
- Não instalar dependências (scaffold Laravel/React) antes da aprovação desta fase.
- Não criar microsserviços, API pública, portal do cliente, editor visual, autocontratação ou self-service — fora de escopo do MVP.

## 12. Prompt Pattern para Novas Tarefas

Toda solicitação ao OpenCode deve conter: Contexto, Objetivo, Escopo, Regras, Integração, Testes, Critérios de aceite, Relatório final (arquivos criados/modificados, testes, pendências).
