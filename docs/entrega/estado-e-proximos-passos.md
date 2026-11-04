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

Proteção confirmada em 03/11/2026 após a confirmação de identidade pelo
proprietário. A regra exige PR, as três verificações GitHub Actions (Linux
PHP 8.4/8.5 e Windows PHP 8.5), branch atualizada e conversas resolvidas.
A regra vale também para administradores. Force push e exclusão estão bloqueados.
Não exige aprovação de outra conta, permitindo trabalho individual via PR e CI.

A API pública da branch informa `protected: true`; os critérios foram conferidos
na tela de edição da regra salva. O conector não tem acesso ao endpoint detalhado
de administração da proteção. [Registro da verificação](protecao-main.json).

[Registro dos merges](integracao-prs.json).

## Pendências para fechar a Fase 1

1. Concluído: Dockerfile/Compose no repositório final, migrations e fluxo HTTP MySQL.
2. Concluído: três rodadas concorrentes disputando saldo, com uma única baixa,
   saldo zero e rollback da OS recusada. [Evidências](../qualidade/fechamento-windows.md).
3. Concluído: suíte MySQL, cobertura 98,71%, auditoria Composer e scan Semgrep.
4. Concluído: PDF acadêmico preenchível com Grupo 19, Gabriel De Barros Neves,
   rm378693 e Discord Nev3s; roteiro de apresentação de 14m30s baseado no enunciado.
5. Ação do integrante: gravar e publicar o vídeo público no YouTube (até 15 minutos),
   inserir a URL real e conferir a branch/hash no PDF. [Como finalizar](finalizar-pdf.md).
6. Publicar a atualização do vídeo por PR, regenerar o PDF com o hash publicado,
   conferir os links sem login e enviar o PDF no canal da faculdade.

Os PDFs em docs/ddd são material de apoio; não substituem o PDF final acadêmico.
Não foi criado link de vídeo fictício, nem preenchida identificação desconhecida.
Não é necessário publicar o backend em um serviço de hospedagem para a execução
local descrita; código, documentos e vídeo precisam estar acessíveis conforme o enunciado.

## Fixar a versão antes da submissão

Após a etapa Windows, registrar `git rev-parse HEAD` e confirmar
`git status --short` limpo. Use esse mesmo hash nos links permanentes dos
artefatos e confira os links sem login. Não considerar o hash deste PR como
versão final antes de concluir as pendências acima.
