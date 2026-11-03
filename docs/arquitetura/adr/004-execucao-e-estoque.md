# ADR 004 - Baixa atômica no início da execução

Data: 03/11/2026 (America/Sao_Paulo).

## Situação

Implementada e validada em SQLite; concorrência MySQL pendente. Registro das decisões já descritas no projeto; não inventa aprovação coletiva.

## Contexto

Aprovação não garante estoque; atendimentos concorrentes não podem consumir o mesmo saldo.

## Decisão

Exigir orçamento vigente aprovado, bloquear OS e saldos, validar todos os materiais e gravar saídas e status na mesma transação. Repetição não duplica baixa.

## Alternativas consideradas

Baixa ao criar OS consome antes de autorização; baixa sem transação permite estado parcial; reserva antecipada acrescenta política não exigida ao MVP.

## Consequências

Concorrência precisa de teste no MySQL. Faltas ou erro de gravação revertem tudo. A aplicação coordena agregados do mesmo monólito.

## Evidências

ES-03; D08/D09; regras-da-os.md e modelo-de-dados.md


Atualização de implementação (03/11/2026): consulte os casos de uso em app/Application e os repositórios transacionais em app/Infrastructure/Persistence. A cadeia de PRs implementa OS, versões, decisão, estoque e indicador; Docker/MySQL executável permanece para o ambiente Windows.
