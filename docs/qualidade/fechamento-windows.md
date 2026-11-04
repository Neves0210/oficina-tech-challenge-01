# Validação final no Windows com Docker e MySQL

Data: 03/11/2026. Ambiente do integrante: Windows, Docker Desktop, containers Linux, PHP 8.4.26, MySQL 8.4.11 e PCOV 1.0.12. A aplicação de negócio não foi alterada nesta etapa; foram acrescentados ambiente, verificações reproduzíveis e documentação.

| Verificação executada | Resultado |
|---|---|
| Suíte nativa Windows PHP 8.4.22 | 137 aprovados, 773 asserções |
| Build Docker, saúde HTTP e migrations MySQL | Aprovados; `/up` e `/api/openapi` retornam 200 |
| Suíte no MySQL exclusivo `oficina_testing` | 136 aprovados, 1 ignorado específico de Windows, 770 asserções |
| Suíte e cobertura em SQLite no container | 136 aprovados, 1 ignorado, 770 asserções |
| Cobertura global de app/ | 687/696 linhas = 98,71% |
| Domínios com linhas executáveis | 100% em Customers, Materials, Orders, Shared e Vehicles; Services contém contratos |
| Fluxo HTTP contra Nginx/PHP-FPM/MySQL | 69 chamadas, 93 asserções e nenhuma falha |
| Concorrência real MySQL | Três rodadas; uma execução bem-sucedida e uma recusada em cada rodada |
| Saldo e atomicidade após disputa | Saldo 0,000; uma única saída; OS recusada sem início e sem histórico parcial |
| Composer audit | Nenhum aviso conhecido e nenhum pacote abandonado |
| Semgrep p/php | 23 regras, 84 arquivos de app/ e routes/, 0 achados e 0 erros |
| Composer validate e Pint | Aprovados |
| Script PowerShell de preparação | Gera APP_KEY/JWT quando vazios e preserva as chaves existentes |

## Evidências permanentes

Todos os arquivos abaixo ficam em [evidencias/windows-final](evidencias/windows-final). Relatórios brutos do Newman e o ambiente Postman ficam somente em `build/`, ignorado no Git, por conterem credenciais e tokens.

- [Ambiente, métricas e domínios](evidencias/windows-final/ambiente.json).
- [Suíte MySQL](evidencias/windows-final/mysql-testes.txt).
- [Resultado concorrente](evidencias/windows-final/mysql-concorrencia.json).
- [Saída de cobertura e testes](evidencias/windows-final/cobertura-testes.txt).
- [Clover](evidencias/windows-final/clover.xml), [JUnit](evidencias/windows-final/junit.xml) e [HTML compactado](evidencias/windows-final/cobertura-html.zip).
- [Resumo HTTP sem tokens](evidencias/windows-final/postman-resumo.json).
- [Auditoria Composer](evidencias/windows-final/composer-audit.json) e [análise estática](evidencias/windows-final/semgrep-resumo.json).
- [Manifesto SHA-256 do código e ambiente](evidencias/windows-final/codigo.sha256.json).

O [guia Docker](../entrega/docker-windows.md) contém os comandos para repetir as verificações. A CI acrescenta um job para build, suíte MySQL, concorrência e cobertura. A execução local não substitui a aprovação efetiva dos checks de um PR.

## Escopo e limites

O cenário concorrente usa o repositório de execução real em processos PHP independentes, com conexões MySQL distintas e uma barreira de bloqueio controlada. Os orçamentos aprovados são fixtures de preparação; a autenticação e a decisão do cliente são verificadas separadamente pela suíte HTTP e pela coleção. A validação cobre a disputa de duas ordens pelo mesmo material, sem pretender ser um teste de carga completo.

A análise de segurança cobre dependências e as regras PHP da ferramenta, com revisão dos controles documentados. Não é pentest nem garantia de ausência de falhas desconhecidas. O vídeo público e o preenchimento do seu link no PDF dependem da gravação do integrante.
