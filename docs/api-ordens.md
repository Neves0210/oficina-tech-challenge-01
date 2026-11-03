# Abertura e diagnóstico de ordens de serviço

APIs administrativas protegidas pelo JWT existente. Cadastre cliente, veículo,
serviços e materiais antes de abrir a OS.

`POST /api/service-orders` recebe:

```json
{
  "customer_document": "529.982.247-25",
  "vehicle_plate": "BRA1A23",
  "complaint": "Troca de óleo",
  "items": [
    {"kind":"SERVICE","catalog_id":1,"quantity":"1"},
    {"kind":"SUPPLY","catalog_id":2,"quantity":"0.500"}
  ]
}
```

Documento e placa usam as validações existentes e são normalizados. Cliente e
veículo devem existir; vínculo incorreto retorna 409. A criação é transacional,
nasce em `Recebida` e registra histórico. `items` é opcional e pode ser vazio.
IDs são os dos respectivos cadastros; `kind` distingue SERVICE, PART e SUPPLY.
Peças e serviços exigem quantidade inteira, insumos admitem três casas.

Cada item guarda nome, unidade, quantidade, preço em centavos e subtotal.
O cliente da API não informa preço. O subtotal é arredondado individualmente
em metade para cima, sem ponto flutuante, e o total soma os subtotais. O limite
monetário é 9007199254740991 centavos, preservando precisão de inteiros JSON.
Catálogo alterado não reescreve itens existentes; substituir os itens captura
os preços então vigentes. Material inativo não pode entrar na OS.

- `GET /api/service-orders`: lista paginada, mais recentes primeiro, com quantidade de itens.
- `GET /api/service-orders/{id}`: detalha itens, histórico e `items_total_cents`.
- `POST /api/service-orders/{id}/diagnosis`, corpo `{"diagnosis":"Diagnóstico técnico"}`: passa de Recebida a Em diagnóstico; atualizar o texto nessa etapa não duplica a transição.
- `PUT /api/service-orders/{id}/items`, corpo `{"items":[...]}`: substitui todos os itens enquanto Recebida ou Em diagnóstico. Lista vazia limpa os itens. Uma falha preserva a lista anterior.

Não há atualização genérica de status nem exclusão de OS. Cadastros ligados a
uma OS não podem ser excluídos. Unidade de material referenciado também não
pode mudar. Abrir/diagnosticar a ordem não aprova reparo nem consome estoque.

Orçamento e decisão do cliente são descritos em `api-orcamentos.md` e
`api-cliente.md`. Execução e entrega estão em `api-execucao.md`. Os seis status
são definidos no domínio. Falhas seguem 401/403, 404, 409 e 422 das APIs
administrativas.
