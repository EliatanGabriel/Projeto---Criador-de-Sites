# WEBSUDIO OS
## Documento Mestre de Produto, Negócio e Arquitetura

**Versão:** 1.0  
**Status:** Especificação inicial aprovada para planejamento  
**Tipo:** Sistema interno de gestão empresarial e produção de sites  
**Modelo:** Plataforma web privada (Cloud)  
**Público inicial:** Equipe interna de três pessoas  
**Idioma:** Português brasileiro

---

# 1. VISÃO GERAL

## 1.1. O que é o WebStudio OS?

O WebStudio OS será uma plataforma web interna para administrar uma empresa especializada na criação, desenvolvimento, publicação e manutenção de sites para clientes.

A plataforma centralizará todas as etapas da operação empresarial, desde a entrada de um novo cliente até a entrega e manutenção do site.

O sistema deverá reduzir processos manuais, centralizar informações, padronizar a produção e permitir que a equipe trabalhe de maneira organizada.

## 1.2. Objetivos principais

- Centralizar clientes e informações comerciais.
- Organizar demandas recebidas pelo setor comercial.
- Transformar demandas aprovadas em projetos.
- Organizar tarefas e responsáveis.
- Acompanhar prazos e andamento.
- Reutilizar estruturas e componentes de sites.
- Automatizar processos repetitivos.
- Administrar sites publicados.
- Controlar receitas, despesas e resultados.
- Criar uma base tecnológica que permita futura evolução para SaaS.

## 1.3. Escopo inicial

O WebStudio OS será exclusivamente interno.

Não haverá, na primeira versão:

- Cadastro público de usuários.
- Área de login para clientes.
- Portal externo de acompanhamento.
- Editor visual público.
- Autocontratação de planos.
- Criação de sites pelo próprio cliente.

O acesso será concedido somente aos integrantes autorizados da equipe.

---

# 2. ESTRUTURA ORGANIZACIONAL

## 2.1. Equipe inicial

### Eliatan — Gestão técnica e arquitetura

Responsabilidades:

- Administrar a plataforma.
- Definir arquitetura dos projetos.
- Analisar demandas recebidas.
- Definir tecnologias.
- Organizar a produção.
- Distribuir atividades.
- Acompanhar desenvolvimento.
- Revisar qualidade.
- Coordenar publicação e entrega.
- Administrar a evolução tecnológica da empresa.

### Alisson — Comercial e administração comercial

Responsabilidades:

- Prospectar clientes.
- Realizar atendimento.
- Apresentar serviços.
- Negociar contratos.
- Registrar vendas.
- Cadastrar clientes.
- Coletar briefing.
- Receber materiais e referências.
- Registrar valores e prazos comerciais.
- Encaminhar demandas para análise técnica.
- Manter relacionamento com clientes.

### Roger — Desenvolvimento e programação

Responsabilidades:

- Implementar interfaces.
- Desenvolver funcionalidades.
- Programar componentes.
- Integrar serviços e APIs.
- Trabalhar com banco de dados quando necessário.
- Corrigir problemas.
- Realizar testes técnicos.
- Participar da manutenção dos sites.

A plataforma deverá permitir que os usuários acumulem responsabilidades, sem limitar rigidamente suas atividades.

---

# 3. FLUXO OPERACIONAL DA EMPRESA

O fluxo abaixo representa o funcionamento oficial da operação.

1. Alisson conquista um cliente.
2. Alisson cadastra o cliente na plataforma.
3. Alisson registra o serviço contratado.
4. Alisson preenche o briefing.
5. Alisson anexa logotipos, imagens e referências.
6. Alisson registra informações comerciais.
7. Alisson envia a demanda.
8. Eliatan recebe a notificação.
9. Eliatan analisa a demanda.
10. Caso existam informações faltantes, a demanda retorna para complementação.
11. Eliatan aprova a demanda.
12. O sistema transforma a demanda em projeto.
13. O sistema gera tarefas conforme o modelo de projeto escolhido.
14. Eliatan define responsáveis e prioridades.
15. Roger executa as atividades técnicas.
16. Eliatan acompanha e revisa o desenvolvimento.
17. São realizadas correções.
18. O site é preparado para publicação.
19. Eliatan registra a entrega.
20. O projeto passa para concluído ou manutenção.

**Regra central:** nenhuma demanda comercial deve entrar automaticamente em desenvolvimento sem passar pela análise técnica.

---

# 4. ARQUITETURA DO SISTEMA

## 4.1. Arquitetura recomendada

Adotar arquitetura de monólito modular.

O sistema terá uma aplicação principal, organizada em módulos independentes por responsabilidade.

Não criar microsserviços na primeira versão.

### Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 |
| Frontend | React |
| Integração frontend/backend | Inertia.js |
| Linguagem frontend | TypeScript |
| Linguagem backend | PHP |
| Banco de dados | PostgreSQL |
| Estilização | Tailwind CSS |
| Componentes visuais | Biblioteca React compatível |
| Autenticação | Laravel |
| Autorização | Policies e controle de permissões |
| Arquivos | Armazenamento compatível com S3 |
| Versionamento | Git e GitHub |
| Desenvolvimento | OpenCode |
| Hospedagem | Cloud |
| Deploy inicial | Render ou infraestrutura equivalente |

As versões deverão ser verificadas no momento da implementação, utilizando versões estáveis e compatíveis.

### Justificativa

O Laravel será responsável por:

- Regras de negócio.
- Autenticação.
- Autorização.
- Validação.
- Banco de dados.
- Processamento de demandas.
- Geração de projetos.
- Notificações.
- Integrações.
- Processos automatizados.

O React será responsável por:

- Interfaces administrativas.
- Dashboard.
- Formulários.
- Tabelas.
- Painéis de gestão.
- Componentes interativos.
- Experiência de navegação.

O Inertia.js permitirá integrar as duas camadas sem a necessidade de criar inicialmente uma API REST independente para todas as operações.

## 4.2. Organização arquitetural

A aplicação deverá ser organizada por domínios:

- Authentication
- Users
- Clients
- Commercial
- Demands
- Projects
- Tasks
- Briefings
- Templates
- Websites
- Finance
- Notifications
- Settings
- Reports

Cada domínio deverá possuir responsabilidades bem definidas.

Evitar concentrar todas as regras de negócio em controllers gigantes.

Utilizar Services ou Actions para operações importantes, como converter uma demanda em projeto.

---

# 5. MÓDULOS FUNCIONAIS

## MÓDULO 01 — AUTENTICAÇÃO E USUÁRIOS

Objetivo: controlar o acesso à plataforma.

Funcionalidades:

- Login por e-mail e senha.
- Logout.
- Recuperação de senha.
- Alteração de senha.
- Gerenciamento de usuários.
- Ativação e desativação de contas.
- Controle de sessões.
- Registro do último acesso.

O cadastro de usuários será realizado por um administrador autorizado.

Não haverá registro público.

## MÓDULO 02 — DASHBOARD

Objetivo: apresentar uma visão geral da operação.

Indicadores:

- Total de clientes.
- Novos clientes.
- Demandas recebidas.
- Demandas aguardando análise.
- Projetos ativos.
- Projetos em desenvolvimento.
- Projetos bloqueados.
- Projetos em revisão.
- Sites publicados.
- Entregas próximas.
- Tarefas pendentes.
- Receita prevista.
- Receita recebida.

Elementos visuais:

- Cards de indicadores.
- Gráfico de projetos por status.
- Gráfico de receitas.
- Lista de atividades recentes.
- Lista de prazos próximos.
- Demandas recentes.
- Tarefas atribuídas ao usuário.

Os indicadores financeiros deverão considerar somente registros efetivamente cadastrados, sem confundir valores contratados com pagamentos recebidos.

## MÓDULO 03 — CLIENTES

Objetivo: manter o cadastro centralizado dos clientes.

Dados:

- Nome da empresa.
- Nome fantasia.
- Nome do responsável.
- CNPJ ou CPF, quando aplicável.
- E-mail.
- Telefone.
- WhatsApp.
- Endereço.
- Site atual.
- Instagram.
- Observações.
- Origem do cliente.
- Responsável comercial.
- Situação do relacionamento.

Status:

- Prospecto.
- Cliente ativo.
- Cliente inativo.
- Cliente arquivado.

Cada cliente terá uma página própria contendo:

- Dados cadastrais.
- Histórico comercial.
- Demandas.
- Propostas.
- Projetos.
- Sites.
- Pagamentos.
- Arquivos.
- Histórico de atividades.

## MÓDULO 04 — COMERCIAL

Objetivo: administrar o processo de venda.

Funcionalidades:

- Cadastro de oportunidades.
- Registro de contatos.
- Histórico de negociação.
- Cadastro de propostas.
- Registro de serviços vendidos.
- Valores negociados.
- Condições comerciais.
- Previsão de fechamento.
- Responsável pela venda.

Etapas comerciais:

- Novo contato.
- Em negociação.
- Proposta enviada.
- Aguardando retorno.
- Venda fechada.
- Venda perdida.

A conversão de uma oportunidade em cliente deverá evitar cadastros duplicados.

O módulo comercial deverá ser separado da produção técnica.

## MÓDULO 05 — DEMANDAS

Objetivo: receber solicitações comerciais e encaminhá-las para análise técnica.

Essa será uma das principais funcionalidades da primeira versão.

### Cadastro da demanda

Informações:

- Cliente.
- Responsável comercial.
- Título da demanda.
- Tipo de serviço.
- Descrição.
- Objetivo do site.
- Público-alvo.
- Funcionalidades desejadas.
- Referências visuais.
- Identidade visual.
- Domínio.
- Hospedagem.
- Prazo solicitado.
- Valor negociado.
- Condições comerciais.
- Observações.
- Arquivos anexados.

### Tipos de projeto

- Site institucional.
- Landing page.
- Site para restaurante.
- Catálogo de produtos.
- E-commerce.
- Portfólio.
- Sistema web.
- Outros.

### Status da demanda

- Rascunho.
- Enviada.
- Em análise.
- Aguardando informações.
- Aprovada.
- Recusada.
- Convertida em projeto.
- Cancelada.

### Regras

1. Alisson pode criar e editar suas demandas ainda não enviadas.
2. Após o envio, a demanda entra na fila técnica.
3. Eliatan recebe uma notificação.
4. Eliatan pode aprovar, recusar ou solicitar complementação.
5. Demandas aprovadas podem ser convertidas em projetos.
6. Uma demanda convertida não poderá gerar outro projeto por acidente.
7. Toda alteração importante deverá ser registrada no histórico.

## MÓDULO 06 — PROJETOS

Objetivo: administrar os sites contratados e sua produção.

Cada projeto deverá possuir:

- Identificação.
- Cliente relacionado.
- Demanda de origem.
- Tipo de projeto.
- Descrição.
- Escopo contratado.
- Funcionalidades.
- Responsável técnico.
- Equipe envolvida.
- Prioridade.
- Data de início.
- Prazo.
- Valor contratado.
- Status.
- Template utilizado.
- Repositório GitHub, quando existente.
- URL de desenvolvimento.
- URL de produção.
- Observações.

### Status dos projetos

- Planejamento.
- Aguardando materiais.
- Pronto para desenvolvimento.
- Em desenvolvimento.
- Em revisão interna.
- Aguardando retorno do cliente.
- Preparado para publicação.
- Concluído.
- Em manutenção.
- Cancelado.

### Página individual do projeto

Deverá conter abas:

1. Visão geral.
2. Briefing.
3. Tarefas.
4. Arquivos.
5. Atividades.
6. Informações técnicas.
7. Financeiro.

## MÓDULO 07 — TAREFAS

Objetivo: organizar as atividades necessárias para entregar cada projeto.

Funcionalidades:

- Criar tarefas.
- Editar tarefas.
- Excluir ou arquivar tarefas.
- Atribuir responsáveis.
- Definir prioridade.
- Definir prazo.
- Registrar descrição.
- Adicionar comentários.
- Anexar arquivos.
- Registrar bloqueios.
- Acompanhar progresso.

### Status das tarefas

- A fazer.
- Em andamento.
- Bloqueada.
- Em revisão.
- Concluída.

### Prioridades

- Baixa.
- Normal.
- Alta.
- Urgente.

Cada tarefa deverá estar vinculada a um projeto.

A plataforma terá uma visão geral das tarefas da equipe, permitindo filtrar por responsável, projeto, prioridade e prazo.

## MÓDULO 08 — BRIEFING

Objetivo: padronizar a coleta de informações.

O briefing deverá possuir formulários específicos para diferentes categorias.

Exemplo: restaurante.

- Nome do estabelecimento.
- História.
- Endereço.
- Telefone.
- Horário de funcionamento.
- Cardápio.
- Categorias.
- Produtos.
- Preços.
- Imagens.
- Formas de pedido.
- Integração com WhatsApp.
- Referências de design.

Exemplo: site institucional.

- História da empresa.
- Serviços.
- Público-alvo.
- Diferenciais.
- Identidade visual.
- Endereço.
- Contatos.
- Redes sociais.
- Referências.

O briefing deverá ser preenchido pelo comercial e poderá ser complementado durante a análise técnica.

## MÓDULO 09 — TEMPLATES E AUTOMAÇÕES

Objetivo: evitar o desenvolvimento repetitivo.

### Templates de projeto

Cada template será um modelo de planejamento.

Exemplos:

- Restaurante.
- Institucional.
- Landing page.
- Barbearia.
- Clínica.
- Loja virtual.

Cada template poderá possuir:

- Nome.
- Categoria.
- Descrição.
- Checklist padrão.
- Tarefas iniciais.
- Funcionalidades previstas.
- Estrutura técnica.
- Versão.

### Automação de projetos

Ao converter uma demanda em projeto:

1. Selecionar o template.
2. Criar o projeto.
3. Gerar tarefas automaticamente.
4. Definir responsáveis padrão.
5. Configurar prioridades.
6. Calcular ou registrar prazos.
7. Registrar o histórico da conversão.

A geração deverá ser transacional: se ocorrer uma falha, não poderá deixar o projeto parcialmente criado.

### Futuro: templates de sites

Em uma etapa posterior, serão adicionados modelos de código reutilizáveis.

Esses modelos poderão conter:

- Estrutura de páginas.
- Componentes.
- Layouts.
- Estilos.
- Funcionalidades.
- Configurações.
- Integrações.

É importante distinguir templates de projeto, que organizam o trabalho, de templates de site, que fornecem código reutilizável.

## MÓDULO 10 — SITES PUBLICADOS

Objetivo: manter o inventário dos sites entregues.

Informações:

- Cliente.
- Projeto de origem.
- Nome do site.
- Domínio.
- Provedor de hospedagem.
- Repositório.
- URL de produção.
- Data de publicação.
- Status.
- Certificado SSL.
- Responsável técnico.
- Plano de manutenção.
- Observações.

Status:

- Em desenvolvimento.
- Publicado.
- Suspenso.
- Em manutenção.
- Desativado.

## MÓDULO 11 — FINANCEIRO

Objetivo: acompanhar a saúde financeira da operação.

Funcionalidades:

- Receitas previstas.
- Receitas recebidas.
- Receitas pendentes.
- Despesas.
- Custos de hospedagem.
- Custos de domínio.
- Custos de ferramentas.
- Custos operacionais.
- Histórico de pagamentos.
- Relatórios mensais.
- Resultado operacional.

### Regras financeiras

Separar:

- Valor negociado.
- Valor contratado.
- Valor faturado.
- Valor recebido.
- Valor pendente.
- Despesas efetivamente pagas.

Não considerar um contrato como dinheiro recebido.

A integração com gateways de pagamento será implementada posteriormente.

## MÓDULO 12 — NOTIFICAÇÕES

Objetivo: manter os integrantes informados.

Notificações internas para:

- Nova demanda recebida.
- Demanda aprovada.
- Demanda recusada.
- Solicitação de informações.
- Projeto criado.
- Tarefa atribuída.
- Tarefa bloqueada.
- Prazo próximo.
- Projeto concluído.

Cada usuário poderá consultar suas notificações e marcar como lidas.

## MÓDULO 13 — RELATÓRIOS

Indicadores:

- Projetos entregues por mês.
- Projetos em andamento.
- Tempo médio de desenvolvimento.
- Demandas recebidas.
- Taxa de conversão comercial.
- Receita mensal.
- Despesas mensais.
- Resultado operacional.
- Distribuição de tarefas.
- Produtividade da operação.

Os relatórios deverão utilizar dados reais da plataforma.

---

# 6. MODELO DE DADOS E BANCO DE DADOS

Banco principal: PostgreSQL.

O modelo deverá ser relacional, com chaves estrangeiras, índices, restrições e integridade referencial.

## 6.1. Tabelas principais

### Usuários e permissões

- users
- roles
- permissions
- role_user
- permission_role

### Comercial e clientes

- clients
- leads
- lead_activities
- proposals
- proposal_items

### Demandas

- demands
- demand_briefings
- demand_attachments
- demand_status_history

### Projetos

- projects
- project_members
- project_tasks
- task_comments
- task_attachments
- project_activities

### Templates

- project_templates
- project_template_tasks
- website_templates
- website_template_versions

### Sites

- websites
- website_domains
- website_deployments
- website_maintenance_records

### Financeiro

- financial_transactions
- financial_categories
- payment_records
- expense_records

### Sistema

- notifications
- activity_logs
- settings
- uploaded_files

## 6.2. Relacionamentos

- Um cliente pode possuir vários leads históricos.
- Um cliente pode possuir várias demandas.
- Uma demanda pertence a um cliente.
- Uma demanda pode originar no máximo um projeto.
- Um projeto pertence a um cliente.
- Um projeto pode possuir várias tarefas.
- Uma tarefa pertence a um projeto.
- Uma tarefa pode possuir comentários e anexos.
- Um projeto pode utilizar um template.
- Um projeto pode possuir vários integrantes.
- Um cliente pode possuir vários sites.
- Um site pode possuir vários domínios.
- Um site pode possuir vários registros de publicação.
- Uma transação financeira pode estar relacionada a um cliente ou projeto.

## 6.3. Regras de integridade

- Utilizar UUIDs ou identificadores seguros conforme decisão de implementação.
- Utilizar timestamps.
- Utilizar índices nas colunas de pesquisa.
- Impedir duplicidade de e-mail quando aplicável.
- Garantir integridade entre cliente, demanda e projeto.
- Utilizar exclusão lógica para registros importantes.
- Não apagar histórico financeiro.
- Não apagar registros de auditoria.
- Utilizar transações de banco em operações críticas.

## 6.4. Preparação para evolução SaaS

Mesmo sendo interno, o banco deverá manter uma separação clara entre dados comerciais, projetos e usuários.

Não será necessário implementar multi-tenancy completo na primeira versão.

Entretanto, a arquitetura não deverá depender de identificadores globais ou regras que impeçam uma futura separação dos dados por organização.

---

# 7. PERMISSÕES E SEGURANÇA

## 7.1. Perfis

### OWNER / ADMINISTRADOR

Responsável: Eliatan.

Permissões:

- Acesso completo.
- Gerenciar usuários.
- Gerenciar clientes.
- Gerenciar demandas.
- Aprovar demandas.
- Gerenciar projetos.
- Distribuir tarefas.
- Gerenciar templates.
- Consultar financeiro.
- Consultar relatórios.
- Configurar plataforma.

### COMERCIAL

Responsável: Alisson.

Permissões:

- Cadastrar clientes.
- Consultar clientes.
- Gerenciar oportunidades.
- Criar propostas.
- Criar demandas.
- Editar demandas não enviadas.
- Enviar demandas.
- Consultar situação dos projetos.
- Consultar informações comerciais autorizadas.

Restrições:

- Não pode aprovar demandas.
- Não pode alterar configurações técnicas.
- Não pode administrar usuários.
- Não pode modificar tarefas técnicas sem autorização.
- Não pode acessar configurações financeiras restritas.

### DESENVOLVEDOR

Responsável: Roger.

Permissões:

- Consultar projetos atribuídos.
- Consultar briefings.
- Consultar arquivos necessários.
- Visualizar tarefas.
- Atualizar tarefas atribuídas.
- Adicionar comentários.
- Anexar arquivos.
- Registrar bloqueios.
- Consultar informações técnicas.

Restrições:

- Não pode aprovar demandas.
- Não pode alterar negociações comerciais.
- Não pode administrar usuários.
- Não pode alterar valores contratados.
- Não pode acessar informações financeiras restritas.

## 7.2. Segurança obrigatória

- Senhas armazenadas com hashing seguro.
- Proteção CSRF.
- Validação no backend.
- Autorização no backend.
- Controle de sessão.
- Rate limiting no login.
- Proteção de uploads.
- Validação de extensões e tamanho de arquivos.
- URLs privadas para arquivos confidenciais.
- Variáveis sensíveis em ambiente seguro.
- HTTPS obrigatório em produção.
- Logs de ações importantes.
- Backups periódicos.

Esconder botões na interface não será considerado controle de permissão suficiente. Toda operação deverá ser validada no servidor.

---

# 8. EXPERIÊNCIA E INTERFACE

## 8.1. Identidade visual

O sistema deverá transmitir profissionalismo, organização e produtividade.

Direção visual:

- Interface moderna.
- Layout administrativo.
- Menu lateral fixo.
- Conteúdo central.
- Cards informativos.
- Tabelas organizadas.
- Filtros.
- Busca global.
- Feedback visual.
- Responsividade.

## 8.2. Estrutura de navegação

Menu lateral:

1. Dashboard.
2. Comercial.
3. Clientes.
4. Demandas.
5. Projetos.
6. Tarefas.
7. Templates.
8. Sites publicados.
9. Financeiro.
10. Relatórios.
11. Equipe.
12. Configurações.

Os itens serão exibidos de acordo com as permissões do usuário.

## 8.3. Requisitos de usabilidade

- Interface em português brasileiro.
- Datas no padrão brasileiro.
- Valores monetários em BRL.
- Feedback de sucesso e erro.
- Estados vazios informativos.
- Confirmação para operações destrutivas.
- Paginação.
- Filtros combinados.
- Pesquisa por cliente e projeto.
- Navegação consistente.

---

# 9. HOSPEDAGEM E INFRAESTRUTURA

## 9.1. Ambiente

O sistema será hospedado na nuvem.

Arquitetura inicial:

- Aplicação Laravel.
- PostgreSQL gerenciado.
- Armazenamento de arquivos externo.
- HTTPS.
- Domínio próprio.
- Backups.
- Logs.

## 9.2. Ambientes separados

### Desenvolvimento

Utilizado por Eliatan e Roger.

### Homologação

Utilizado para validar funcionalidades antes de disponibilizar atualizações.

### Produção

Utilizado pela equipe na operação diária.

Nunca utilizar dados reais de produção em testes sem autorização e proteção adequada.

## 9.3. Estratégia de publicação

- Código versionado no GitHub.
- Branch principal protegida.
- Branches de funcionalidades.
- Deploy automático após aprovação.
- Migrações controladas.
- Variáveis de ambiente separadas.
- Procedimento de rollback.

A hospedagem definitiva será escolhida após avaliação de compatibilidade, custo, backups, persistência e suporte a processos PHP e filas.

---

# 10. ROADMAP DE DESENVOLVIMENTO

O desenvolvimento será dividido em fases independentes.

Cada fase deverá ser implementada, testada e validada antes de avançar.

## FASE 0 — Planejamento e preparação

Objetivo: preparar a documentação e o ambiente.

Entregas:

- Documento mestre.
- Arquitetura.
- Modelo de dados.
- Definição de padrões.
- Estrutura do repositório.
- AGENTS.md.
- Estratégia de Git.
- Configuração inicial do OpenCode.

## FASE 1 — Fundação técnica

Objetivo: criar a base da aplicação.

Entregas:

- Projeto Laravel.
- Integração React/Inertia.
- TypeScript.
- Tailwind CSS.
- PostgreSQL.
- Layout principal.
- Autenticação.
- Migrações iniciais.
- Seeders.
- Tratamento de erros.

Critério de conclusão: aplicação funcional, com login e banco conectado.

## FASE 2 — Equipe e permissões

Entregas:

- Cadastro de usuários.
- Perfis.
- Permissões.
- Policies.
- Gerenciamento de sessões.
- Proteção das rotas.
- Testes de autorização.

Critério de conclusão: cada integrante acessa somente as funcionalidades autorizadas.

## FASE 3 — Dashboard e clientes

Entregas:

- Dashboard inicial.
- Indicadores básicos.
- Cadastro de clientes.
- Edição.
- Consulta.
- Histórico.
- Pesquisa.
- Filtros.

Critério de conclusão: a equipe consegue cadastrar e administrar clientes.

## FASE 4 — Comercial e demandas

Entregas:

- Pipeline comercial.
- Cadastro de oportunidades.
- Propostas básicas.
- Formulário de demanda.
- Briefing.
- Upload de arquivos.
- Envio para análise.
- Histórico de status.
- Notificações.

Critério de conclusão: Alisson consegue fechar uma venda, registrar todas as informações e encaminhar uma demanda para Eliatan.

## FASE 5 — Projetos e tarefas

Entregas:

- Análise técnica.
- Aprovação de demandas.
- Conversão de demanda em projeto.
- Cadastro de projetos.
- Distribuição de tarefas.
- Responsáveis.
- Prazos.
- Comentários.
- Histórico.
- Dashboard de produção.

Critério de conclusão: Eliatan e Roger conseguem trabalhar nos projetos sem depender de controles externos.

## FASE 6 — Templates e automações

Entregas:

- Cadastro de templates de projeto.
- Modelos de tarefas.
- Geração automática de tarefas.
- Definição de responsáveis padrão.
- Automações de status.
- Notificações automáticas.

Critério de conclusão: novos projetos podem ser preparados com modelos reutilizáveis.

## FASE 7 — Sites e publicação

Entregas:

- Inventário de sites.
- Repositórios.
- Domínios.
- Hospedagem.
- Ambientes.
- Histórico de publicação.
- Integração com GitHub.
- Integração com infraestrutura de deploy.

Critério de conclusão: a plataforma centraliza informações dos sites produzidos e facilita sua publicação.

## FASE 8 — Financeiro e relatórios

Entregas:

- Receitas.
- Despesas.
- Pagamentos.
- Custos por projeto.
- Indicadores financeiros.
- Relatórios.
- Exportação de dados.

Critério de conclusão: a administração consegue acompanhar a operação financeira.

## FASE 9 — Evolução SaaS

Entregas futuras:

- Biblioteca de templates de código.
- Geração automatizada de sites.
- Editor visual.
- Gerenciamento avançado de domínios.
- Configuração automática de hospedagem.
- Integrações de pagamento.
- Assinaturas.
- Portal opcional para clientes.
- Multi-tenancy.
- Autocontratação.

Esta fase não faz parte do MVP.

---

# 11. METODOLOGIA DE DESENVOLVIMENTO COM OPENCODE

## 11.1. Princípio de trabalho

O OpenCode deverá receber tarefas pequenas e específicas.

Não solicitar a construção de módulos inteiros sem análise prévia.

O desenvolvimento deverá seguir o ciclo:

1. Analisar.
2. Planejar.
3. Implementar.
4. Testar.
5. Revisar.
6. Documentar.
7. Aprovar.
8. Avançar.

## 11.2. Documentação do repositório

Criar inicialmente:

- docs/PRD.md
- docs/ARCHITECTURE.md
- docs/DATABASE.md
- docs/OPERATIONS.md
- docs/ROADMAP.md
- docs/SECURITY.md
- docs/DEVELOPMENT.md
- docs/DECISIONS.md
- AGENTS.md

O documento mestre deverá ser a referência central.

Alterações importantes de escopo ou arquitetura deverão ser registradas em DECISIONS.md.

## 11.3. Padrão dos prompts

Cada prompt enviado ao OpenCode deverá possuir:

**Contexto:** informações sobre o projeto.

**Objetivo:** o que deverá ser desenvolvido.

**Escopo:** funcionalidades incluídas.

**Regras:** limitações e padrões obrigatórios.

**Integração:** como a implementação se relaciona com os módulos existentes.

**Testes:** o que deverá ser validado.

**Critérios de aceite:** como saberemos que está concluído.

**Relatório final:** arquivos criados, modificados, testes executados e pendências.

## 11.4. Controle de versão

- Trabalhar com Git.
- Criar branches por funcionalidade.
- Evitar desenvolvimento diretamente na main.
- Fazer commits pequenos.
- Revisar alterações antes de mesclar.
- Não versionar arquivos .env.
- Não expor credenciais.
- Executar testes antes de integrar alterações.

## 11.5. Regra de continuidade

O OpenCode deverá consultar AGENTS.md e a documentação pertinente antes de executar novas tarefas.

Não deverá modificar módulos não relacionados à tarefa sem justificativa.

Não deverá remover funcionalidades existentes para resolver problemas pontuais.

Não deverá criar dados fictícios em produção.

---

# 12. CRITÉRIOS GERAIS DE QUALIDADE

O WebStudio OS deverá atender aos seguintes requisitos:

### Funcionalidade

Todas as operações principais devem funcionar de ponta a ponta.

### Segurança

Autenticação e autorização devem ser verificadas no backend.

### Integridade

Operações críticas devem preservar a consistência dos dados.

### Manutenibilidade

Código modular, documentado e organizado.

### Testabilidade

Testes automatizados para regras de negócio, permissões e operações críticas.

### Responsividade

Interface utilizável em computadores, tablets e dispositivos móveis.

### Desempenho

Paginação, índices, consultas eficientes e carregamento otimizado.

### Confiabilidade

Backups, logs e mecanismos de recuperação.

---

# 13. ESCOPO DO MVP

O primeiro lançamento operacional deverá permitir:

1. Eliatan, Alisson e Roger acessarem o sistema.
2. Cada usuário possuir permissões próprias.
3. Alisson cadastrar clientes.
4. Alisson registrar vendas e demandas.
5. Alisson preencher briefings.
6. Alisson anexar materiais.
7. Eliatan receber demandas.
8. Eliatan analisar e aprovar demandas.
9. O sistema criar projetos.
10. Eliatan distribuir tarefas.
11. Roger atualizar atividades.
12. Eliatan acompanhar a produção.
13. A equipe consultar o histórico.
14. A equipe identificar prazos e pendências.

**O MVP não precisa criar sites automaticamente.**

Sua primeira missão é organizar a operação da empresa e fazer com que os três integrantes consigam trabalhar dentro de um único ambiente.

---

# 14. VISÃO DE LONGO PRAZO

O WebStudio OS deverá evoluir de ferramenta interna para uma plataforma capaz de automatizar grande parte da produção de sites.

Visão futura:

- Um cliente é cadastrado.
- Um serviço é vendido.
- O briefing é preenchido.
- Um modelo de site é selecionado.
- O ambiente é criado automaticamente.
- Os componentes são preparados.
- O conteúdo é inserido.
- O desenvolvedor personaliza o projeto.
- Os testes são executados.
- O site é publicado.
- O sistema acompanha sua manutenção.

A plataforma deverá permitir que a empresa aumente o número de clientes sem aumentar proporcionalmente o trabalho manual.

---

# 15. DEFINIÇÃO FINAL DO PRODUTO

**Nome:** WebStudio OS

**Categoria:** Sistema interno de gestão empresarial e produção digital.

**Usuários iniciais:** Eliatan, Alisson e Roger.

**Operação:** comercial, administrativa e técnica.

**Hospedagem:** cloud.

**Acesso externo:** não permitido na primeira versão.

**Arquitetura:** monólito modular.

**Backend:** Laravel.

**Frontend:** React + Inertia.js + TypeScript.

**Banco de dados:** PostgreSQL.

**Desenvolvimento assistido:** OpenCode.

**Objetivo inicial:** centralizar clientes, demandas, projetos e tarefas.

**Objetivo intermediário:** padronizar e automatizar a produção de sites.

**Objetivo futuro:** transformar a plataforma em um SaaS de criação e gerenciamento de sites.

**Diretriz principal:** desenvolver uma operação empresarial funcional primeiro e automatizar progressivamente os processos que já estiverem consolidados.

---

**FIM DO DOCUMENTO MESTRE — WEBSUDIO OS v1.0**