# Orçamentos versionados

Rotas administrativas com JWT:

- `POST /api/service-orders/{id}/budgets`: calcula e apresenta uma nova versão, sem valor informado pelo consumidor da API. Exige Em diagnóstico, diagnóstico preenchido e itens não vazios. Responde 201 com itens copiados, total, versão, decisão PENDING e instante de apresentação. A OS passa a Aguardando aprovação.
- `GET /api/service-orders/{id}/budgets`: histórico paginado, versões mais recentes primeiro.
- `GET /api/service-orders/{id}/budgets/{budget}`: consulta uma versão da própria OS.
- `POST /api/service-orders/{id}/revise-budget`: inicia revisão antes da execução; volta de Aguardando aprovação para Em diagnóstico, preservando todas as propostas e decisões anteriores.

Cada versão é imutável. Não há edição ou exclusão de orçamento apresentado.
Os valores são os itens registrados na OS, sem recálculo silencioso a partir
do preço atual do catálogo. Depois de revisar, alterar os itens e apresentar
outra proposta cria versão incremental com nova decisão PENDING.

Versão e status são gravados com os snapshots numa única transação, sob bloqueio
da OS. Repetir a apresentação enquanto aguardando aprovação retorna 409 e não
duplica proposta. A revisão também não é permitida depois de iniciar execução.

O cliente consulta e decide pela API específica descrita em `api-cliente.md`.
Gerar orçamento não aprova reparo nem consome estoque.
