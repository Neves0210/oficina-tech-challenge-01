# Regras da ordem de serviço

Este documento fixa as decisões de domínio da Fase 1. As regras deverão aparecer no código e nos testes dos próximos PRs. As seis etapas abaixo são as exigidas pelo enunciado; aprovação e rejeição são decisões do orçamento, não status adicionais da OS.

## Abertura e diagnóstico

1. A OS pertence a um único cliente e a um único veículo cadastrado para esse cliente. O cliente é localizado por CPF/CNPJ e o veículo pela placa.
2. A OS nasce em **Recebida**. O registro inicial pode conter serviços solicitados, mas ainda não autoriza sua execução.
3. O início do diagnóstico leva a OS a **Em diagnóstico**. O diagnóstico registra o problema encontrado e permite definir serviços, peças e insumos com quantidades positivas.
4. Serviços e materiais precisam existir nos respectivos catálogos. O preço e a unidade de cada item são copiados para a OS, para preservar o histórico caso o catálogo mude depois.

## Orçamento e decisão

5. O orçamento é a soma dos subtotais dos serviços, peças e insumos: `quantidade × preço unitário registrado na OS`. O cálculo usa valores monetários exatos, sem ponto flutuante. O valor não é digitado separadamente.
6. Ao disponibilizar o orçamento ao cliente, a OS passa a **Aguardando aprovação**. A proposta registra seus itens, total e momento de apresentação.
7. A decisão do cliente deve ser registrada explicitamente, junto do momento em que ocorreu. Aprovar uma proposta permite iniciar apenas os itens daquela proposta.
8. Rejeitar a proposta impede a execução. A OS permanece em **Aguardando aprovação** após a rejeição; quando a equipe inicia uma revisão, ela retorna a **Em diagnóstico**. A revisão gera uma nova versão da proposta, preservando a decisão anterior. O enunciado não exige status de cancelamento.
9. Qualquer alteração de itens ou preços depois da apresentação invalida a proposta vigente e exige nova apresentação e aprovação antes de executar.

## Execução e estoque

10. A passagem a **Em execução** exige orçamento vigente aprovado e estoque suficiente para todas as peças e insumos previstos.
11. A baixa de materiais e a mudança de status acontecem na mesma transação. Se faltar material, nenhuma baixa parcial nem mudança de status deve persistir. Toda entrada ou saída fica registrada em movimentação de estoque.
12. Peças usam quantidade inteira; insumos podem usar quantidade fracionária conforme sua unidade cadastrada. Estoque não pode ficar negativo.
13. A OS passa a **Finalizada** quando a execução autorizada termina, e a **Entregue** quando o veículo é devolvido ao cliente. Esses momentos são registrados para histórico e cálculo de tempo.

## Transições permitidas

| De | Para | Ação necessária |
| --- | --- | --- |
| Recebida | Em diagnóstico | Iniciar diagnóstico |
| Em diagnóstico | Aguardando aprovação | Apresentar orçamento calculado |
| Aguardando aprovação | Em diagnóstico | Revisar diagnóstico ou itens após rejeição |
| Aguardando aprovação | Em execução | Iniciar execução de orçamento aprovado, com estoque suficiente |
| Em execução | Finalizada | Finalizar serviços autorizados |
| Finalizada | Entregue | Registrar entrega do veículo |

Qualquer outra transição é recusada. A decisão de aprovar ou rejeitar não altera sozinha o status. Uma proposta rejeitada pode ser substituída; uma proposta aprovada não pode ser modificada silenciosamente.

## Consulta e indicador

- O cliente pode consultar o andamento da própria OS por API, com acesso vinculado àquela ordem. CPF/CNPJ isolado não concede acesso a dados da OS. O mecanismo concreto desse acesso será definido junto da API de acompanhamento; as APIs administrativas usarão JWT, como exige o enunciado.
- O tempo médio de execução considera apenas OS que chegaram a **Finalizada**: média dos intervalos entre entrada em **Em execução** e entrada em **Finalizada**. OS ainda em execução não entram na média. Sem OS finalizada, o indicador não inventa um valor.
