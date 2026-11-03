# Validação de execução e entrega

Execução em 03/11/2026, PHP 8.5.8 e SQLite, sem Docker:

- 132 testes aprovados, 1 ignorado por ser específico de Windows, 748 asserções.
- Cobertura: 666/675 linhas de app/ (98,67%).
- Coleção Postman via Newman no backend HTTP: 68 requisições, 92 asserções, sem falhas.
- OpenAPI validada com Swagger Parser; estilo e diff-check passaram.

Verificados: aprovação vigente; rejeição de aprovação antiga; agregação de
linhas do mesmo material; falta de um material impedindo todas as baixas;
rollback após gravações de estoque; início repetido sem consumo duplicado;
serviço sem material; instantes e transições até entrega; acompanhamento pelo
cliente. As movimentações da OS possuem restrição única no banco.

Bloqueios de linha e tentativas de transação estão no código, mas testes
sequenciais de SQLite não demonstram concorrência no MySQL. Essa validação
continua pendente para o ambiente posterior, sem Docker nesta etapa.
