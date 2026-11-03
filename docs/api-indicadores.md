# Tempo médio de execução

`GET /api/reports/service-execution-time` exige JWT administrativo.

Resposta: `{"data":{"orders_count":2,"average_seconds":900}}`.
Sem ordens elegíveis: `{"data":{"orders_count":0,"average_seconds":null}}`.

O indicador considera todas as OS Finalizadas e Entregues, uma vez por OS,
calculando `soma(finished_at - started_at) / quantidade`, em segundos e com
arredondamento para duas casas decimais. O intervalo começa no início da execução
e termina na finalização; não inclui espera de aprovação nem espera de retirada.
Uma execução de zero segundos é válida. Ordens abertas ou com datas ausentes ou
invertidas são excluídas. Não há filtro de período nesta versão.

A consulta usa cursor e o cálculo pertence ao domínio, sem função SQL específica
de SQLite/MySQL. O acesso do cliente não permite consultar o indicador global.
