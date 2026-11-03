# Estado da entrega e sequência de integração

Data: 03/11/2026. Este documento acompanha a versão consolidada dos PRs.
A main não deve ser apresentada como concluída enquanto os PRs estiverem abertos.

| PR | Conteúdo | Dependência |
|---|---|---|
| #4 | Correção da dependência vulnerável e CI | Já integrado na main |
| #5 | Peças, insumos e estoque | #4 |
| #6 | Abertura, diagnóstico e itens de OS | #5 |
| #7 | Orçamentos versionados | #6 |
| #8 | Acompanhamento e decisão do cliente | #7 |
| #9 | Execução, baixa, finalização e entrega | #8 |
| #10 | Indicador de tempo médio | #9 |
| #11 | Cobertura por domínio e regressão Windows | #10 |
| PR desta branch docs/delivery-evidence | DDD atualizado, PDFs, instruções e evidências permanentes | #11 |

Os PRs foram encadeados para que cada diff mostre sua própria etapa. Revisar e
integrar do menor número para o maior, retargetando o próximo para main quando
necessário e conferindo a CI. Nenhum merge é executado por esta documentação.

## Pendências para fechar a Fase 1

1. Revisar/integrar os PRs e proteger a main contra push direto, exigindo PR e
   verificações de qualidade; conferir a regra com a conta do proprietário.
2. No Windows, preparar Dockerfile/Compose e a execução MySQL acordada. Validar
   migrations, fluxo completo e duas execuções concorrentes disputando saldo:
   apenas a operação com estoque suficiente deve persistir; nunca saldo negativo.
3. Refazer testes/auditoria e atualizar as evidências após qualquer alteração.
4. Conferir documentos e links públicos; os três fluxos e diagramas vigentes
   estão no GitHub. FigJam mantém o retrato anterior, explicitamente histórico.
5. Gravar o vídeo público no YouTube (até 15 minutos), mostrando a versão final
   em execução e os cenários principais. Vídeo a cargo do integrante.
6. Montar o PDF final com integrantes, identificadores pedidos, repositório,
   documentação, vídeo e a mesma branch/commit demonstrada. PDF a cargo do integrante.

Os PDFs em docs/ddd são material de apoio; não substituem o PDF final acadêmico.
Não foi criado link de vídeo fictício, nem preenchida identificação desconhecida.
Não é necessário publicar o backend em um serviço de hospedagem para a execução
local descrita; código, documentos e vídeo precisam estar acessíveis conforme o enunciado.

## Fixar a versão antes da submissão

Após a integração e a etapa Windows, registrar `git rev-parse HEAD` e confirmar
`git status --short` limpo. Use esse mesmo hash nos links permanentes dos
artefatos e confira os links sem login. Não considerar o hash deste PR como
versão final antes de concluir as pendências acima.
