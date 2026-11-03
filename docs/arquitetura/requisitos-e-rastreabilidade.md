# Rastreabilidade dos requisitos da Fase 1

Referência: enunciado do Tech Challenge fornecido pelo integrante. Esta tabela
registra a implementação e as pendências, sem transformar decisões do MVP em
requisitos adicionais. Data: 03/11/2026; código da cadeia de PRs #4 a #11.
As funcionalidades abaixo ainda dependem da integração dos PRs abertos na main.

| Requisito | Implementação ou evidência | Situação |
|---|---|---|
| Backend REST monolítico em camadas | app/Http, Application, Domain, Infrastructure; ADR 001 | Implementado |
| Banco escolhido e justificado | ADR 002; migrations; configuração MySQL | Escolhido MySQL; execução/concorrência pendentes |
| Administradores autenticados com JWT | AdminToken, AuthenticateAdmin, TokenController | Implementado e testado |
| Validar CPF/CNPJ e placa | DocumentNumber, Plate; testes unitários e HTTP | Implementado |
| Clientes, veículos e serviços CRUD | Registries, contratos, repositórios e rotas | Implementado |
| Peças/insumos CRUD e estoque | MaterialRegistry, Quantity, movimentações e saldo | Implementado |
| Abrir OS por cliente/veículo; adicionar itens | OrderRegistry; POST /api/service-orders | Implementado |
| Orçamento calculado e disponibilizado | BudgetRegistry; versões imutáveis e API do cliente | Implementado |
| Aprovar/rejeitar orçamento | CustomerPortal; decisão explícita da proposta vigente | Implementado |
| Acompanhar andamento da própria OS | Credencial restrita à OS; histórico e seis status | Implementado |
| Fluxo de status e consumo de materiais | OrderStatus, OrderExecution; baixa e status atômicos | Implementado; concorrência MySQL pendente |
| Lista/detalhe de OS | OrderController e repositório | Implementado |
| Tempo médio de execução | GET /api/reports/service-execution-time | Implementado no PR #10 |
| Testes unitários/integração e cobertura >=80% | Relatório consolidado, Clover/JUnit/HTML e CI | Local 98,71%; cada domínio executável 100% |
| OpenAPI e instruções de execução | docs/openapi.json, GET /api/openapi, coleção, README e guia Windows | Implementado; conferir no PC do integrante |
| Dockerfile e Compose | Etapa posterior Windows | Pendente por acordo com o integrante |
| DDD: três Event Storming, atores, eventos, políticas, agregados e VO | docs/ddd/event-storming.md e PDF; catálogo de diagramas e fontes | Revisão atual publicada neste PR |
| Diagramas e compartilhamento público sem permissão | Markdown/Mermaid e PDFs no GitHub público | Publicado na branch; FigJam histórico identificado |
| Análise de vulnerabilidades no repositório | docs/seguranca/relatorio-final.md e audit JSON | Sem avisos conhecidos na consulta atual |
| Repositório público e desenvolvimento por PR | Neves0210/oficina-tech-challenge-01 | Público; PRs abertos em cadeia |
| Proteção da main | Configuração de regras no GitHub | Pendente; não ativada por este PR |
| Vídeo público YouTube, até 15 minutos | A produzir pelo integrante no Windows | Pendente |
| PDF final com integrantes e links coerentes com a versão | A produzir pelo integrante após fechar a versão | Pendente |

O canal de entrega da credencial ao cliente é manual; envio de e-mail/WhatsApp
não foi implementado porque a disponibilização por API atende à alternativa do
MVP. Não há frontend no escopo implementado. GitHub hospeda código e documentação;
a entrega acadêmica final segue o canal indicado pela disciplina.
