# Event Storming da oficina

Revisão de 03/11/2026. Base de código `4f67f81954b6990a5506e3fece2501720115743a` (PRs #4 a #11).

Esta é a versão vigente dos três fluxos, publicada online neste repositório público.
[PDF de apoio](event-storming.pdf) e [fonte editável](fontes/event-storming.json).
O quadro FigJam original é histórico e continua identificado no [índice](indice-quadro.md).

Atores operacionais representam responsabilidades de negócio; no código, a equipe
usa o papel administrador. Eventos descrevem fatos, sem fila ou publicação
de eventos customizados. Não se afirma que houve workshop com os integrantes.

Legenda: ator amarelo, comando azul, evento laranja, política roxa, agregado verde.
Consultas são visões, sem evento novo de negócio.

## ES-01 - Criação da Ordem de Serviço

Fluxo implementado na cadeia de PRs. Eventos descrevem fatos de negócio; não há barramento de eventos.

| Ator | Comando | Evento ou visão | Política | Agregado | Situação |
|---|---|---|---|---|---|
| Atendente | Cadastrar cliente | Cliente cadastrado | CPF/CNPJ válido e único. Se já existir, consultar o cadastro. | Cliente | Implementado; evento conceitual |
| Atendente | Cadastrar veículo | Veículo cadastrado | Placa válida e única; cliente existente. Se já existir, consultar. | Veículo | Implementado; evento conceitual |
| Atendente | Abrir OS | OS recebida | Localizar por CPF/CNPJ e placa; verificar responsável e registrar solicitação. | Ordem de Serviço | Implementado; evento conceitual |
| Mecânico | Iniciar diagnóstico | Diagnóstico iniciado | Transição: Recebida para Em diagnóstico. | Ordem de Serviço | Implementado; evento conceitual |
| Mecânico | Definir itens | Itens da OS definidos | Serviços e materiais existentes; quantidades positivas; preços copiados. | Ordem de Serviço / Item | Implementado; evento conceitual |
| Sistema, por ação da equipe | Gerar orçamento | Orçamento gerado | Somar subtotais exatos; arredondar por item; preservar versão. | OS / Orçamento | Implementado; evento conceitual |
| Atendente | Apresentar orçamento | Orçamento apresentado | Disponibilizar ao cliente; registrar momento; Aguardando aprovação. | OS / Orçamento | Implementado; evento conceitual |

A abertura localiza cliente por CPF/CNPJ normalizado e veículo pela placa, verificando o responsável.

Gerar e apresentar orçamento são uma única ação na API: POST /service-orders/{order}/budgets. A versão apresentada é imutável.

Cadastros, OS, itens e orçamentos estão implementados. A modelagem não afirma a realização de workshop presencial.

## ES-02 - Acompanhamento e execução da OS

Fluxo implementado na cadeia de PRs. Eventos descrevem fatos de negócio; não há barramento de eventos.

| Ator | Comando | Evento ou visão | Política | Agregado | Situação |
|---|---|---|---|---|---|
| Cliente | Aprovar orçamento | Orçamento aprovado | Decisão explícita sobre a versão vigente; registrar data e titularidade. | OS / Orçamento | Implementado; evento conceitual |
| Equipe da oficina | Iniciar execução | Execução iniciada | Exigir aprovação e saldo; baixar materiais e mudar status na mesma transação. | OS + Saldo de Material | Implementado; evento conceitual |
| Mecânico | Finalizar execução | OS finalizada | Partir de Em execução; registrar finalizada_em. | Ordem de Serviço | Implementado; evento conceitual |
| Atendente | Registrar entrega | Veículo entregue | Partir de Finalizada; registrar entregue_em e preservar histórico. | Ordem de Serviço | Implementado; evento conceitual |
| Cliente | Consultar andamento | VISÃO: andamento da OS | Autorizar apenas a própria OS. CPF/CNPJ isolado não concede acesso. | Consulta de OS | Consulta implementada |
| Administrador | Consultar tempo médio | VISÃO: tempo de execução | Média entre início e fim; incluir finalizadas e entregues; vazio = null e zero OS. | Consulta gerencial | Consulta implementada |

Alternativa: Cliente -> Rejeitar orçamento -> Orçamento rejeitado. Impede execução; OS permanece Aguardando aprovação. Equipe -> Iniciar revisão -> Revisão iniciada -> Em diagnóstico; nova versão exige nova decisão.

Sem estoque: recusar início e reverter toda a transação. Sem aprovação: recusar início. Repetição da ação não pode consumir materiais novamente. Após iniciar, itens aprovados não podem mudar silenciosamente.

Acesso do cliente: token aleatório específico da OS, validade de 24 horas, somente hash SHA-256 persistido; pode ser rotacionado ou revogado pelo administrador.

## ES-03 - Gestão de peças, insumos e estoque

Fluxo implementado na cadeia de PRs. Eventos descrevem fatos de negócio; não há barramento de eventos.

| Ator | Comando | Evento ou visão | Política | Agregado | Situação |
|---|---|---|---|---|---|
| Administrador | Cadastrar material | Material cadastrado | Distinguir peça e insumo; definir unidade e preço não negativo. | Material | Implementado; evento conceitual |
| Administrador | Registrar entrada | Entrada de estoque registrada | Quantidade positiva; registrar motivo, instante e material. | Saldo de Material | Implementado; evento conceitual |
| Mecânico | Incluir material na OS | Material incluído na OS | Copiar unidade e preço. Inclusão não consome estoque. | Ordem de Serviço | Implementado; evento conceitual |
| Equipe da oficina | Iniciar execução aprovada | Saída de estoque registrada | Bloquear saldos; verificar todos os itens; saída e status em transação única. | Saldo + OS | Implementado; evento conceitual |
| Administrador | Alterar cadastro | Material atualizado | Alteração de preço não muda itens e orçamentos históricos. | Material | Implementado; evento conceitual |
| Administrador | Excluir ou inativar | Material retirado do catálogo | Preservar referências históricas. Política de exclusão/inativação depende do vínculo. | Material | Implementado; evento conceitual |
| Administrador | Consultar saldo | VISÃO: saldo e movimentações | Peça inteira; insumo admite decimal exato; saldo nunca negativo. | Consulta de estoque | Consulta implementada |

Concorrência: duas OS não podem consumir o mesmo saldo. A aplicação coordena os contextos dentro de um único banco, com transação e bloqueio dos saldos.

Falha em qualquer item impede baixa parcial e início da execução. Tentativas rejeitadas não são movimentações de saída bem-sucedidas.

O enunciado exige controle de estoque, mas não fixa momento da baixa, unidades ou política de exclusão. Estes detalhes são decisões de projeto documentadas; não requisitos literais adicionais.

Bloqueios implementados; concorrência real em MySQL ainda depende de validação no ambiente Windows posterior.

## Relação com o código

- Cadastros: `Application/Registration`, `DocumentNumber`, `Plate` e repositórios Eloquent.
- Catálogo/estoque: `MaterialRegistry`, `Quantity`, `EloquentMaterialRepository`.
- OS: `OrderRegistry`, `OrderStatus`, `Money`, `EloquentOrderRepository`.
- Orçamento: `BudgetRegistry`, `BudgetDecision`, `EloquentBudgetRepository`.
- Cliente: `CustomerPortal`, `EloquentCustomerAccessRepository` e middleware `AuthenticateOrderCustomer`.
- Execução: `OrderExecution`, `EloquentExecutionRepository`; baixa e status em transação.
- Indicador: `ExecutionTimeReport`, `ExecutionTimeSummary`, `EloquentExecutionTimeRepository`.

As implementações estão sob `app/`. Os testes ficam em `tests/Unit` e `tests/Feature`.
A validação sequencial em SQLite não comprova a concorrência MySQL.
