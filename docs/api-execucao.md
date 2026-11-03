# Execução, finalização e entrega

Rotas administrativas com JWT, sem alteração genérica de status:

- `POST /api/service-orders/{id}/start-execution`: exige Aguardando aprovação e orçamento vigente APPROVED. Soma quantidades do mesmo material nos itens aprovados, verifica todos os saldos e grava uma saída por material. A OS passa a Em execução e recebe started_at.
- `POST /api/service-orders/{id}/finish`: exige Em execução, grava finished_at e passa a Finalizada.
- `POST /api/service-orders/{id}/deliver`: exige Finalizada, grava delivered_at e passa a Entregue.

Respostas 200 contêm a OS atualizada e seu histórico. Ações inválidas, repetidas,
sem aprovação vigente, com falta de estoque ou instantes inconsistentes retornam
409. Recursos ausentes retornam 404; autenticação segue 401/403. Não há corpo
de entrada de status, quantidade, orçamento ou horário: esses valores são definidos
pelo sistema e pela proposta aprovada.

Início e consumo são uma transação: bloqueia a OS e os materiais em ordem de ID,
verifica todos os saldos antes de escrever, grava saídas ligadas à OS e muda o
status. Uma falha reverte saldo, movimentações, status e instante. Uma restrição
única também impede duplicação de saída do mesmo material na mesma OS. O início
repetido é recusado; consulte a OS antes de repetir uma requisição de resultado incerto.

Serviço sem material não exige estoque. Inativar material bloqueia novas inclusões
em OS, mas não impede consumir uma proposta já aprovada com estoque disponível.
Depois de iniciar, os itens e a proposta não podem ser revisados neste MVP.
O cliente continua consultando o andamento com a credencial da própria OS.

Os testes locais validam regras, repetição e rollback no SQLite. Bloqueio de
linha e concorrência real em MySQL ainda serão validados quando esse ambiente
estiver disponível. Docker não faz parte desta etapa.
