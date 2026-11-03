# ADR 002 - MySQL como banco da solução

Data: 03/11/2026 (America/Sao_Paulo).

## Situação

Escolha documentada; ambiente MySQL ainda não validado nesta execução. Registro das decisões já descritas no projeto; não inventa aprovação coletiva.

## Contexto

Clientes, veículos, OS e estoque têm relações, unicidade e operações atômicas.

## Decisão

Adotar MySQL para o ambiente da solução; manter SQLite em memória nos testes rápidos atuais. Fixar imagem e versão quando Docker for implementado.

## Alternativas consideradas

PostgreSQL atende de forma semelhante; SQLite simplifica testes, mas não foi escolhido como banco final. Banco documental não oferece vantagem demonstrada para este modelo.

## Consequências

FK e transações preservam relações. SQLite não prova bloqueios e concorrência do MySQL; validar concorrência no banco escolhido na etapa Windows.

## Evidências

D02/D06/D07; config/database.php; phpunit.xml; documentação de arquitetura existente


Atualização de implementação (03/11/2026): consulte os casos de uso em app/Application e os repositórios transacionais em app/Infrastructure/Persistence. A cadeia de PRs implementa OS, versões, decisão, estoque e indicador; Docker/MySQL executável permanece para o ambiente Windows.
