# Cobertura por domínio e regressão Windows

`composer test:coverage` verifica o mínimo global de 80% de linhas de `app/`
e, em seguida, exige 80% em cada domínio de `app/Domain/` com linhas executáveis.
Contratos sem implementação são identificados, sem percentual artificial.
O verificador recusa relatório ausente, inválido ou sem domínio encontrado.
Os separadores de caminho são normalizados para Windows e Unix.

Em 03/11/2026: Customers 23/23; Materials 17/17; Orders 22/22;
Shared 10/10; Vehicles 4/4. Services contém apenas contrato, sem linhas executáveis.
Foram verificados também os caminhos de falha: relatório ausente e relatório
Clover de teste com as linhas de Orders descobertas retornaram código 1.

A suíte manteve 136 testes aprovados, 1 ignorado no macOS, 770 asserções,
e 687/696 linhas cobertas em app/ (98,71%). O checkout do GitHub Actions agora
usa `oficina-avaliação`, para que a regressão de entrada de console em caminho
com acentos seja executada no job Windows, em vez de ignorada.

A execução na máquina Windows do integrante e o ambiente MySQL/Docker continuam
separados desta validação automatizada.
