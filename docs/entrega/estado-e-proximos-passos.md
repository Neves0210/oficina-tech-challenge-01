# Estado da entrega e sequência de integração

Data: 03/11/2026. Os PRs #5 a #12 foram revisados e integrados sequencialmente
na main, preservando o histórico de cada etapa. Todos tiveram CI aprovada.
A árvore consolidada do commit `9a4853de2733f076d4940b390b907b0600f178ff`
é idêntica à versão de código e documentação validada no PR #12.

| PR | Conteúdo | Situação |
|---|---|---|
| #4 | Dependência corrigida e CI | Integrado |
| #5 | Peças, insumos e estoque | Integrado |
| #6 | Abertura, diagnóstico e itens de OS | Integrado |
| #7 | Orçamentos versionados | Integrado |
| #8 | Acompanhamento e decisão do cliente | Integrado |
| #9 | Execução, baixa, finalização e entrega | Integrado |
| #10 | Indicador de tempo médio | Integrado |
| #11 | Cobertura por domínio e regressão Windows | Integrado |
| #12 | DDD, PDFs, guia Windows e evidências permanentes | Integrado |

## Proteção da main

A regra foi preparada para exigir PR, três verificações GitHub Actions (Linux
PHP 8.4/8.5 e Windows PHP 8.5), branch atualizada e conversas resolvidas, sem
force push nem exclusão e com aplicação aos administradores. O GitHub exigiu
confirmação de identidade da conta ao salvar; a ativação depende dessa confirmação.
Nenhuma senha ou código deve ser enviado no chat. A configuração ainda deve ser
verificada como ativa nas configurações do repositório antes da entrega.

[Registro dos merges](integracao-prs.json).

## Pendências para fechar a Fase 1

1. No Windows, preparar Dockerfile/Compose e a execução MySQL acordada. Validar
   migrations, fluxo completo e duas execuções concorrentes disputando saldo:
   apenas a operação com estoque suficiente deve persistir; nunca saldo negativo.
2. Refazer testes/auditoria e atualizar as evidências após qualquer alteração.
3. Conferir documentos e links públicos; os três fluxos e diagramas vigentes
   estão no GitHub. FigJam mantém o retrato anterior, explicitamente histórico.
4. Gravar o vídeo público no YouTube (até 15 minutos), mostrando a versão final
   em execução e os cenários principais. Vídeo a cargo do integrante.
5. Montar o PDF final com integrantes, identificadores pedidos, repositório,
   documentação, vídeo e a mesma branch/commit demonstrada. PDF a cargo do integrante.

Os PDFs em docs/ddd são material de apoio; não substituem o PDF final acadêmico.
Não foi criado link de vídeo fictício, nem preenchida identificação desconhecida.
Não é necessário publicar o backend em um serviço de hospedagem para a execução
local descrita; código, documentos e vídeo precisam estar acessíveis conforme o enunciado.

## Fixar a versão antes da submissão

Após a etapa Windows, registrar `git rev-parse HEAD` e confirmar
`git status --short` limpo. Use esse mesmo hash nos links permanentes dos
artefatos e confira os links sem login. Não considerar o hash deste PR como
versão final antes de concluir as pendências acima.
