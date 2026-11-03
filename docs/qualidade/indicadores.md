# Validação do indicador de tempo

Execução em 03/11/2026, PHP 8.5.8 e SQLite, sem Docker:

- 136 testes aprovados, 1 ignorado por ser específico de Windows, 770 asserções.
- Cobertura de app/: 687/696 linhas (98,71%).
- Coleção Postman via Newman no backend HTTP: 69 requisições, 93 asserções, sem falhas.
- OpenAPI validada com Swagger Parser; estilo e diff-check passaram.

O indicador foi verificado com média fracionária, execução de duração zero,
ordens entregues sem contabilizar espera de retirada e exclusão de ordens
abertas, datas ausentes ou invertidas. Sem conclusões, retorna quantidade zero e
média null. JWT administrativo é obrigatório; credencial de cliente é recusada.

Esses testes usam SQLite e não comprovam concorrência de estoque em MySQL.
