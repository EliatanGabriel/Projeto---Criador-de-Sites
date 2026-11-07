# ROADMAP — WebStudio OS

**Versão:** 1.2 — Fase 6.2  
**Fonte:** Doc.md v1.0 §10, §13, §14

## Visão Geral

Desenvolvimento em fases independentes. Cada fase deve ser implementada, testada e validada antes de avançar. **O MVP não precisa criar sites automaticamente** (Doc.md §13) — primeiro organizar a operação para que Eliatan, Alisson e Roger trabalhem em um único ambiente.

## FASE 0 — Planejamento e preparação (ATUAL)

**Objetivo:** preparar documentação e ambiente.  
**Entregas:** Documento mestre (Doc.md v1.0), arquitetura (`ARCHITECTURE.md`), modelo de dados (`DATABASE.md`), definição de padrões, estrutura do repositório, `AGENTS.md`, estratégia de Git, configuração inicial do OpenCode, `docs/{PRD,OPERATIONS,ROADMAP,SECURITY,DEVELOPMENT,DECISIONS}.md`.  
**Critério de conclusão:** documentação aprovada por Eliatan (aguardando aprovação para Fase 1).

## FASE 1 — Fundação técnica

**Objetivo:** criar a base da aplicação.  
**Entregas:**

- Projeto Laravel 13 (`php -v` 8.3+) + integração React 19 / Inertia v3 + TypeScript ^5.9 + Tailwind CSS.
- PostgreSQL 16/17 (confirmar versão) + migrations iniciais (`users`, `sessions`, `notifications`, `failed_jobs`).
- Layout principal (menu lateral fixo, topbar, layout autenticado/guest).
- Autenticação (login/logout/recuperação) — Breeze **OU** Fortify [decisão pendente em `DECISIONS.md`].
- Seeders (papéis/perfis, usuário OWNER).
- Tratamento de erros (páginas 403/404/500 em pt-BR), validação, i18n básico (datas `dd/mm/yyyy`, moeda BRL).
- CI básico (GitHub Actions: composer, npm, testes).
- Critério de conclusão: aplicação funcional, com login e banco conectado.

## FASE 2 — Equipe e permissões

**Entregas:** cadastro de usuários (criação por administrador, ativação/desativação), perfis (OWNER, COMERCIAL, DESENVOLVEDOR), permissões (Policies), gerenciamento de sessões, proteção de rotas, testes de autorização (Feature tests por perfil).  
**Critério de conclusão:** cada integrante acessa somente funcionalidades autorizadas (testado no backend).

## FASE 3 — Dashboard e clientes

**Entregas:** dashboard inicial (indicadores básicos: clientes, demandas, projetos, tarefas, receitas), cadastro/edição/consulta de clientes, histórico, pesquisa, filtros, página 360° do cliente.  
**Critério de conclusão:** equipe consegue cadastrar e administrar clientes.

## FASE 4 — Comercial e demandas

**Entregas:** pipeline comercial (oportunidades, propostas), cadastro de demandas (formulário completo), briefing (formulários por categoria), upload de arquivos (S3-compatível), envio para análise, histórico de status, notificações (nova demanda → Eliatan).  
**Critério de conclusão:** Alisson consegue fechar venda, registrar todas as informações e encaminhar demanda para Eliatan.

## FASE 5 — Projetos e tarefas

**Entregas:** análise técnica (aprovar/recusar/solicitar complementação), conversão transacional demanda→projeto, cadastro de projetos (abas: visão geral, briefing, tarefas, arquivos, atividades, técnica, financeiro), distribuição de tarefas (responsáveis, prazos, prioridades, comentários, bloqueios), histórico, dashboard de produção.  
**Critério de conclusão:** Eliatan e Roger conseguem trabalhar nos projetos sem controles externos. **Incluir teste transacional completo (rollback em falha).**

## FASE 6 — Templates e automações

**Entregas:** cadastro de templates de projeto (checklist, tarefas iniciais, funcionalidades, estrutura técnica, versão), modelos de tarefas, geração automática de tarefas na conversão, responsáveis padrão, automações de status, notificações automáticas.  
**Critério de conclusão:** novos projetos podem ser preparados com modelos reutilizáveis.

## FASE 7 — Sites e publicação

**Entregas:** inventário de sites (clientes, projetos origem, domínios, hospedagem, repositórios, URLs, datas, SSL, responsáveis, manutenção), histórico de publicação (deployments), integração básica com GitHub (links e metadados).  
**Critério de conclusão:** plataforma centraliza informações dos sites produzidos e facilita publicação. (Integração de deploy automático é opcional nesta fase.)

## FASE 8 — Financeiro e relatórios

**Entregas:** receitas (previstas/recebidas/pendentes), despesas, pagamentos (parcelamentos), custos por projeto, indicadores financeiros (sem confundir contratado com recebido), relatórios mensais, exportação de dados (CSV/PDF [PENDENTE biblioteca]).  
**Critério de conclusão:** administração consegue acompanhar operação financeira.

## FASE 9 — Evolução SaaS (FORA DO MVP)

Entregas futuras: biblioteca de templates de código, geração automatizada de sites, editor visual, gerenciamento avançado de domínios, configuração automática de hospedagem, integrações de pagamento, assinaturas, portal opcional para clientes, multi-tenancy, autocontratação.  
**Esta fase não faz parte do MVP.**

## Ordem de Execução e Dependências

Fase 0 → 1 → 2 → 3 → 4 → 5 (MVP completo) → 6 → 7 → 8. Fases 6–8 podem ser reordenadas conforme prioridade operacional, mas 5 (conversão transacional) é pré-requisito para 6. Feedback da equipe (Eliatan/Alisson/Roger) após cada fase antes de avançar (ciclo: Analisar → Planejar → Implementar → Testar → Revisar → Documentar → Aprovar → Avançar).

## Estimativa de Esforço (não realizada nesta fase)

Nenhuma estimativa de tempo foi registrada na Fase 0. Ser detalhada após aprovação da Fase 1, com base na biblioteca de componentes e driver de filas escolhidos (pendentes).
