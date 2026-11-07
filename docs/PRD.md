# PRD — WebStudio OS

**Versão:** 1.1 — Fase 5
**Fonte:** Doc.md v1.0 §1, §2, §5, §8, §13, §15  
**Status:** Aguardando aprovação antes de implementação

## 1. Visão e Objetivos

O WebStudio OS é uma plataforma web interna para administrar uma empresa especializada em criação, desenvolvimento, publicação e manutenção de sites para clientes. Centralizará todas as etapas da operação, do cadastro do cliente à entrega/manutenção, reduzindo processos manuais e padronizando a produção.

### Objetivos principais (Doc.md §1.2)

- Centralizar clientes e informações comerciais.
- Organizar demandas recebidas pelo comercial.
- Transformar demandas aprovadas em projetos.
- Organizar tarefas e responsáveis.
- Acompanhar prazos e andamento.
- Reutilizar estruturas e componentes de sites (templates de projeto no MVP; templates de código no futuro).
- Automatizar processos repetitivos.
- Administrar sites publicados.
- Controlar receitas, despesas e resultados.
- Criar base tecnológica para futura evolução SaaS (Fase 9, fora do MVP).

## 2. Público e Escopo

### Público inicial (Doc.md §2, §15)

| Pessoa | Papel | Responsabilidades principais |
|---|---|---|
| Eliatan | Gestão técnica/arquitetura (OWNER/Admin) | Administrar plataforma, analisar demandas, arquitetar projetos, distribuir e acompanhar, revisar qualidade, publicar/entregar, evoluir tecnologia |
| Alisson | Comercial | Prospectar, atender, negociar, cadastrar clientes, coletar briefing/materiais, registrar valores/prazos, encaminhar demandas, relacionamento |
| Roger | Desenvolvimento | Implementar interfaces/funcionalidades/componentes, integrar APIs, corrigir, testar, manter sites |

A plataforma deve permitir acúmulo de responsabilidades (um usuário pode ter mais de um perfil/role), sem rigidez exclusiva.

### Dentro do escopo (MVP + evolução progressiva)

Módulos funcionais aprovados (Doc.md §5):

1. Autenticação e Usuários
2. Dashboard
3. Clientes
4. Comercial (leads, oportunidades, propostas)
5. Demandas
6. Projetos
7. Tarefas
8. Briefing
9. Templates e Automações (templates de projeto no MVP)
10. Sites Publicados
11. Financeiro
12. Notificações
13. Relatórios

### Fora do escopo da primeira versão (Doc.md §1.3, §13, §14)

- Cadastro público de usuários / registro aberto.
- Área de login para clientes, portal externo de acompanhamento.
- Editor visual público.
- Autocontratação de planos / criação de sites pelo próprio cliente (self-service).
- Criação automática de sites no MVP (MVP organiza a operação; automação de código é Fase 6/9).
- Gateways de pagamento (integração posterior — Doc.md §11 Financeiro).
- Multi-tenancy completo (preparação arquitetural sim, implementação não).
- Microsserviços (vedados no MVP).

## 3. Fluxo Oficial (Doc.md §3)

Comercial conquista cliente → cadastra cliente → registra serviço contratado → preenche briefing → anexa materiais (logos, imagens, referências) → registra infos comerciais → envia demanda → Eliatan recebe notificação → analisa → [se faltar info: retorna para complementação] → aprova → sistema converte demanda em projeto → gera tarefas conforme template → Eliatan define responsáveis/prioridades → Roger executa → Eliatan acompanha/revisa → correções → preparação para publicação → Eliatan registra entrega → concluído ou manutenção.

**Regra central inviolável:** nenhuma demanda comercial entra automaticamente em desenvolvimento sem passar pela análise técnica.

## 4. Requisitos por Módulo (resumo fiel ao Doc.md §5)

### 4.1 Autenticação e Usuários
Login por e-mail/senha, logout, recuperação/alteração de senha, gerenciamento de usuários por administrador, ativação/desativação, controle de sessões, registro de último acesso. Sem registro público.

### 4.2 Dashboard
Indicadores: total/novos clientes, demandas recebidas/aguardando análise, projetos ativos/em desenvolvimento/bloqueados/em revisão, sites publicados, entregas próximas, tarefas pendentes, receita prevista vs. recebida. Visuais: cards, gráficos (projetos por status, receitas), atividades recentes, prazos próximos, demandas recentes, tarefas do usuário. Financeiro: apenas dados efetivamente cadastrados (nunca confundir contratado com recebido).

### 4.3 Clientes
Dados: nome empresa/fantasia, responsável, CNPJ/CPF, e-mail, telefone, WhatsApp, endereço, site atual, Instagram, observações, origem, responsável comercial, situação. Status: Prospecto, Cliente ativo, Cliente inativo, Cliente arquivado. Página própria com: dados, histórico comercial, demandas, propostas, projetos, sites, pagamentos, arquivos, atividades.

### 4.4 Comercial
Cadastro de oportunidades, contatos, histórico de negociação, propostas, serviços vendidos, valores/condições, previsão de fechamento, responsável. Etapas: Novo contato → Em negociação → Proposta enviada → Aguardando retorno → Venda fechada / Venda perdida. Conversão oportunidade→cliente sem duplicidade. Módulo separado da produção técnica.

### 4.5 Demandas
**Cadastro:** cliente, responsável comercial, título, tipo de serviço, descrição, objetivo, público-alvo, funcionalidades, referências, identidade visual, domínio, hospedagem, prazo, valor negociado, condições, observações, anexos.  
**Tipos:** institucional, landing page, restaurante, catálogo, e-commerce, portfólio, sistema web, outros.  
**Status:** Rascunho, Enviada, Em análise, Aguardando informações, Aprovada, Recusada, Convertida em projeto, Cancelada.  
**Regras:** Alisson cria/edita rascunhos não enviados; após envio entra na fila técnica; Eliatan notificado; pode aprovar/recusar/solicitar complementação; aprovada pode converter; demanda convertida não pode gerar outro projeto por acidente; histórico de alterações importantes.

### 4.6 Projetos
Dados: identificação, cliente, demanda de origem, tipo, descrição, escopo, funcionalidades, responsável técnico, equipe, prioridade, início, prazo, valor contratado, status, template, repo GitHub, URLs dev/prod, observações.  
**Status:** Planejamento, Aguardando materiais, Pronto para desenvolvimento, Em desenvolvimento, Em revisão interna, Aguardando retorno do cliente, Preparado para publicação, Concluído, Em manutenção, Cancelado.  
Página com abas: Visão geral, Briefing, Tarefas, Arquivos, Atividades, Info técnica, Financeiro.

### 4.7 Tarefas
CRUD, responsáveis, prioridade, prazo, descrição, comentários, anexos, bloqueios, progresso. Vínculo obrigatório a projeto. Visão geral filtável por responsável/projeto/prioridade/prazo.  
**Status:** A fazer, Em andamento, Bloqueada, Em revisão, Concluída.  
**Prioridades:** Baixa, Normal, Alta, Urgente.

### 4.8 Briefing
Formulários por categoria (ex.: restaurante: nome, história, endereço, telefone, horários, cardápio, categorias, produtos, preços, imagens, pedido, WhatsApp, referências; institucional: história, serviços, público, diferenciais, identidade, endereço, contatos, redes, referências). Preenchido pelo comercial, complementável na análise técnica.

### 4.9 Templates e Automações
**Templates de projeto (MVP):** nome, categoria, descrição, checklist, tarefas iniciais, funcionalidades, estrutura técnica, versão. Ex.: restaurante, institucional, landing page, barbearia, clínica, loja virtual.  
**Automação:** selecionar template → criar projeto → gerar tarefas → responsáveis padrão → prioridades → prazos → registrar histórico. **Transacional:** falha não pode deixar projeto parcialmente criado.  
**Templates de site (futuro, Fase 9):** código reutilizável (páginas, componentes, layouts, estilos, funcionalidades, config, integrações) — distinto de templates de projeto.

### 4.10 Sites Publicados
Cliente, projeto origem, nome, domínio, hospedagem, repositório, URL produção, data publicação, status, SSL, responsável técnico, manutenção, observações.  
**Status:** Em desenvolvimento, Publicado, Suspenso, Em manutenção, Desativado.

### 4.11 Financeiro
Receitas previstas/recebidas/pendentes, despesas (hospedagem, domínio, ferramentas, operacionais), histórico de pagamentos, relatórios mensais, resultado operacional.  
**Separar obrigatoriamente:** negociado vs. contratado vs. faturado vs. recebido vs. pendente vs. despesa paga. Nunca tratar contrato como dinheiro recebido.

### 4.12 Notificações
Internas para: nova demanda, aprovada, recusada, solicitação de infos, projeto criado, tarefa atribuída/bloqueada, prazo próximo, projeto concluído. Consulta e marcação como lida por usuário.

### 4.13 Relatórios
Projetos entregues/mês, em andamento, tempo médio desenvolvimento, demandas recebidas, taxa conversão comercial, receita/despesas mensais, resultado operacional, distribuição de tarefas, produtividade. Apenas dados reais da plataforma.

## 5. Experiência (Doc.md §8)

Interface moderna administrativa, menu lateral fixo, conteúdo central, cards, tabelas, filtros, busca global, feedback visual, responsiva. Menu: Dashboard, Comercial, Clientes, Demandas, Projetos, Tarefas, Templates, Sites publicados, Financeiro, Relatórios, Equipe, Configurações (filtrado por permissão). pt-BR, datas BR, valores BRL, feedback sucesso/erro, estados vazios informativos, confirmação destrutiva, paginação, filtros combinados, pesquisa por cliente/projeto.

## 6. Escopo do MVP (Doc.md §13)

Permitir que Eliatan, Alisson e Roger acessem com permissões próprias; Alisson cadastre clientes, registre vendas/demandas, preencha briefings, anexe materiais; Eliatan receba/analise/aprove demandas; sistema crie projetos; Eliatan distribua tarefas; Roger atualize atividades; equipe acompanhe, consulte histórico e identifique prazos/pendências.

**O MVP não precisa criar sites automaticamente.**

## 7. Métricas de Aceite do MVP

- Fluxo extremo a extremo Comercial→Análise→Conversão→Tarefas executável sem ferramentas externas.
- Conversão demanda→projeto transacional e idempotente (uma demanda, no máximo um projeto).
- Permissões respeitadas no backend para cada perfil.
- Nenhum dado financeiro confunde contratado com recebido.
- Dashboard exibe indicadores reais das tabelas.
- Uploads validados (tipo/tamanho) e armazenados em S3-compatível com URLs privadas para confidenciais.
- Logs/auditoria de ações importantes.
- Responsivo e em pt-BR.

## 8. Restrições e Não-Requisitos

- Sem portal do cliente, sem pagamento online, sem automação de código na primeira versão.
- Sem multi-tenancy completo; preparar separação lógica de dados.
- Hospedagem definitiva a definir (Render ou equivalente) — pendente avaliação de compatibilidade/custo/backup/persistência/suporte a PHP/filas (Doc.md §9.3).
