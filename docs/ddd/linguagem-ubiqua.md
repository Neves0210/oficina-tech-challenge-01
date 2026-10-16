# Linguagem ubíqua da oficina

Este glossário define os termos usados pela equipe, pela API e pela documentação. Ele evoluirá junto da implementação; os termos abaixo descrevem o MVP da Fase 1.

| Termo | Significado no sistema |
| --- | --- |
| Cliente | Pessoa física ou jurídica identificada por CPF ou CNPJ, responsável pelo atendimento e pela decisão sobre o orçamento. |
| Veículo | Automóvel vinculado a um cliente e identificado pela placa; o cadastro registra também marca, modelo e ano. |
| Serviço | Trabalho técnico oferecido pela oficina, com preço de mão de obra no catálogo. |
| Peça | Componente físico usado no reparo, cadastrado com preço, unidade e quantidade em estoque. |
| Insumo | Material consumido no atendimento, como óleo ou desengraxante, cadastrado com preço, unidade de medida e quantidade em estoque. |
| Ordem de Serviço (OS) | Registro do atendimento de um veículo. Reúne cliente, diagnóstico, itens previstos, orçamento, decisão e andamento até a entrega. |
| Diagnóstico | Registro técnico do problema e dos serviços e materiais necessários, feito antes da proposta ao cliente. |
| Item da OS | Serviço, peça ou insumo incluído na ordem com quantidade e preço registrado para aquele atendimento. |
| Orçamento | Proposta calculada a partir dos itens da OS, apresentada ao cliente antes da execução. |
| Aprovação do orçamento | Decisão expressa do cliente que autoriza a execução dos itens propostos. |
| Rejeição do orçamento | Decisão expressa que impede o início da execução daquele orçamento. |
| Estoque disponível | Quantidade cadastrada de cada peça ou insumo ainda não consumida em uma OS. |
| Movimentação de estoque | Registro de entrada ou saída de uma peça ou insumo, com quantidade e motivo. |
| Status da OS | Etapa do atendimento: Recebida, Em diagnóstico, Aguardando aprovação, Em execução, Finalizada ou Entregue. |
| Tempo de execução | Intervalo entre o início da execução aprovada e a finalização da OS. |

**Distinção essencial:** cadastrar uma OS não significa aprovar o orçamento nem consumir estoque. A execução depende de aprovação explícita e disponibilidade dos materiais.
