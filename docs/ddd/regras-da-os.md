# Regras da ordem de serviço

Este documento fixa as decisões de domínio da Fase 1. As regras estão implementadas na cadeia de PRs #5 a #11. As seis etapas abaixo são as exigidas pelo enunciado; aprovação e rejeição são decisões do orçamento, não status adicionais da OS.

O enunciado exige criação e acompanhamento da OS, orçamento automático, aprovação ou rejeição, controle de estoque e regras de transição. Ele não determina o momento da baixa, o tratamento de uma proposta rejeitada nem a fórmula do tempo médio. As políticas detalhadas abaixo são decisões deste MVP para implementar esses requisitos, documentadas e cobertas por testes.

## Abertura e diagnóstico

1. A OS pertence a um único cliente e a um único veículo cadastrado para esse cliente. O cliente é localizado por CPF/CNPJ e o veículo pela placa.
2. A OS nasce em **Recebida**. O registro inicial pode conter serviços solicitados, mas ainda não autoriza sua execução.
3. O início do diagnóstico leva a OS a **Em diagnóstico**. O diagnóstico registra o problema encontrado e permite definir serviços, peças e insumos com quantidades positivas.
4. Serviços e materiais precisam existir nos respectivos catálogos. O preço e a unidade de cada item são copiados para a OS, para preservar o histórico caso o catálogo mude depois.

## Orçamento e decisão

5. O orçamento é a soma dos subtotais dos serviços, peças e insumos: `quantidade × preço unitário registrado na OS`. Cada subtotal é arredondado para centavos pelo critério de metade para cima; o total soma esses subtotais já arredondados. O cálculo usa valores exatos, sem ponto flutuante. O valor não é digitado separadamente.
6. Ao disponibilizar o orçamento ao cliente, a OS passa a **Aguardando aprovação**. A proposta registra seus itens, total e momento de apresentação.
7. A decisão do cliente deve ser registrada explicitamente, junto do momento em que ocorreu. Aprovar uma proposta permite iniciar apenas os itens daquela proposta.
8. Rejeitar a proposta impede a execução. A OS permanece em **Aguardando aprovação** após a rejeição; quando a equipe inicia uma revisão, ela retorna a **Em diagnóstico**. A revisão gera uma nova versão da proposta, preservando a decisão anterior. O enunciado não exige status de cancelamento.
9. Antes da execução, alterar itens ou preços exige retornar ao diagnóstico e apresentar nova versão; nenhuma decisão anterior autoriza a nova proposta. A proposta anterior permanece registrada. Depois do início da execução, alterações nos itens aprovados são recusadas neste MVP.

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
| Aguardando aprovação | Em diagnóstico | Iniciar revisão da proposta antes da execução |
| Aguardando aprovação | Em execução | Iniciar execução de orçamento aprovado, com estoque suficiente |
| Em execução | Finalizada | Finalizar serviços autorizados |
| Finalizada | Entregue | Registrar entrega do veículo |

Qualquer outra transição é recusada. A decisão de aprovar ou rejeitar não altera sozinha o status. Uma proposta rejeitada pode ser substituída; uma proposta aprovada não pode ser modificada silenciosamente.

## Consulta e indicador

- O cliente pode consultar o andamento da própria OS por API, com acesso vinculado àquela ordem. CPF/CNPJ isolado não concede acesso a dados da OS. Uma credencial aleatória com validade de 24h autoriza apenas a OS correspondente; só o hash é persistido, e o administrador pode rotacionar ou revogar o acesso. As APIs administrativas usam JWT.
- O tempo médio de execução considera OS que chegaram a **Finalizada**, inclusive as já **Entregues**: média dos intervalos entre entrada em **Em execução** e entrada em **Finalizada**. OS ainda em execução não entram na média. Sem OS finalizada, o resultado será `null`, acompanhado de quantidade de OS igual a zero. O indicador mede o atendimento completo, sem dividir o tempo entre serviços da mesma OS.
