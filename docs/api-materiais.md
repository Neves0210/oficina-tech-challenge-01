# Peças, insumos e estoque

Esta API usa o mesmo JWT administrativo dos cadastros. A OpenAPI em
`docs/openapi.json` é a referência do contrato; a coleção Postman preserva
requisições e verificações para demonstração. Não há dependência de Docker.

## Recursos

- `/api/parts`: peças, com unidade fixa `unit` e quantidades inteiras.
- `/api/supplies`: insumos, com unidade informada e quantidades com até três casas decimais.

Ambas as coleções aceitam GET/POST; o caminho `/{id}` aceita GET/PUT/PATCH/DELETE.
Campos: `name`, `description` opcional/nula, `price_cents` inteiro não negativo,
`active` opcional (padrão true). Insumos exigem `unit`, como `L` ou `kg`.
PUT exige todos os campos obrigatórios novamente; PATCH aceita alterações parciais.
O tipo é determinado pelo caminho. Um ID de insumo consultado em `/parts` retorna 404.

O saldo inicial é zero. `stock_quantity` e `type` não podem ser alterados no
cadastro. Preço não altera movimentações anteriores. A unidade não pode mudar
depois da primeira movimentação; a resposta será 409. Material com histórico
não pode ser excluído (409); pode ser inativado com `active: false`.

## Movimentações

`POST /api/parts/{id}/stock-movements` e o equivalente em `/supplies` recebem:

```json
{"direction":"IN","quantity":"2.500","reason":"Compra de óleo"}
```

Use `IN` para entrada e `OUT` para saída administrativa. O exemplo fracionário
é de insumo; para peça, use uma quantidade inteira, como `"2"`. Números inteiros
JSON também são aceitos. Frações devem ser enviadas como strings decimais, sem
vírgula ou expoente. Quantidade deve ser positiva, com no máximo três casas,
até `999999999.999`, e o saldo final deve caber no mesmo limite.

O resultado 201 contém `data.material` (saldo atualizado) e `data.movement`
(quantidade, direção, motivo, autor e datas). GET no mesmo caminho retorna o
histórico paginado. Valores de quantidade na resposta são strings com três
casas, para preservar precisão. Listagens usam `page` e `per_page`, como os cadastros.

Cada movimento grava saldo e histórico na mesma transação. Saída sem saldo ou
entrada que ultrapasse o limite retorna 409 e não grava nada. Não existem
edição/exclusão de movimentações. Repetir um POST cria outra movimentação;
o cliente deve consultar o histórico antes de repetir uma chamada de resultado
incerto. O consumo vinculado à OS será implementado no fluxo de execução.

As operações usam bloqueio de linha no banco que o suporta. Os testes locais
usam SQLite; a validação de concorrência em MySQL continua pendente.

Falhas: 401/403 para acesso, 404 para recurso ausente ou tipo diferente,
409 para regra de negócio/integridade e 422 para entrada inválida.
