# Validação de orçamentos

Execução local de 03/11/2026, PHP 8.5.8 e SQLite, sem Docker:

- 118 testes aprovados, 1 ignorado por ser específico de Windows, 544 asserções.
- Cobertura de linhas: 542/551 (98,37%), com PCOV.
- Coleção Postman via Newman no backend HTTP com banco temporário: 49 requisições, 63 asserções, zero falhas.
- OpenAPI validada com Swagger Parser; Pint e diff-check passaram.

Verificados: cálculo no servidor, snapshot imutável, revisão e incremento de
versão, bloqueio de edição em aprovação, referências históricas, isolamento
por OS e rollback de proposta/status/histórico quando o snapshot falha.
Ainda não há decisão do cliente ou execução nesta etapa.
