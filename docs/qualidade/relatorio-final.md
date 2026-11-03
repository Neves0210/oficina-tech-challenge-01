# Evidências consolidadas da implementação

Data: 03/11/2026. Código de referência: `4f67f81954b6990a5506e3fece2501720115743a`.
A documentação posterior não altera os arquivos da aplicação.

| Verificação | Resultado |
|---|---|
| Suíte local macOS / PHP 8.5.8 / PCOV | 136 aprovados, 1 específico de Windows ignorado; 770 asserções |
| Cobertura de app/ | 687/696 linhas; 98,71% |
| Domínios com linhas executáveis | Todos 100%; Services só possui contrato |
| HTTP local / coleção Postman via Newman | 69 requisições, 93 asserções, nenhuma falha |
| OpenAPI / estilo / Composer validate | Aprovados |
| CI Linux PHP 8.4 e 8.5 | Aprovada |
| CI Windows PHP 8.5 em pasta com acento | 137 aprovados; 773 asserções; regressão executada; cobertura Xdebug 98,9% |

A diferença entre PCOV e Xdebug decorre dos drivers e da execução adicional
específica de Windows. [Execução da CI](https://github.com/Neves0210/oficina-tech-challenge-01/actions/runs/41678660143).

## Arquivos permanentes

- [Ambiente e totais](evidencias/ambiente.json), [saída da suíte](evidencias/testes.txt).
- [Clover](evidencias/clover.xml) e [JUnit](evidencias/junit.xml), com caminhos locais normalizados.
- [HTML compactado](evidencias/cobertura-html.zip): extrair e abrir `html/index.html`.
- [Resumo Postman](evidencias/postman-resumo.json), sem tokens ou respostas sensíveis.
- [Resumo Windows](evidencias/ci-windows.txt).
- [Manifesto de código](evidencias/codigo.sha256.json): vincula os testes aos arquivos implementados.

`composer test:coverage` refaz os relatórios em build/ e aplica os mínimos global
e por domínio. Importe a coleção em docs/postman para refazer o fluxo HTTP com
um banco dedicado. Os artefatos da CI duram 14 dias; estes arquivos permanecem
no Git e devem ser atualizados se o código for alterado antes da entrega.

## Limites

SQLite não comprova bloqueio concorrente em MySQL. Docker/Compose e validação
MySQL ficam para o ambiente Windows do integrante. CI Windows não comprova a
configuração do PC pessoal. A auditoria de dependências tem [relatório próprio](../seguranca/relatorio-final.md).
