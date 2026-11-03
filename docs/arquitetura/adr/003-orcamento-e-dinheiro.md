# ADR 003 - Valores exatos e fotografia de orçamento

Data: 03/11/2026 (America/Sao_Paulo).

## Situação

Implementada em Money, Quantity e nas versões imutáveis de orçamento. Registro das decisões já descritas no projeto; não inventa aprovação coletiva.

## Contexto

Alterações de catálogo não podem reescrever valores aprovados, e cálculo monetário precisa ser exato.

## Decisão

Usar centavos inteiros para preços, decimal exato para quantidades e preservar versões apresentadas com seus itens. Arredondar por item e somar subtotais.

## Alternativas consideradas

Ponto flutuante facilita o código inicial, mas introduz imprecisão; recalcular orçamento pelo preço atual perde rastreabilidade.

## Consequências

Mais dados históricos e regras de versionamento. Nova proposta exige nova decisão. Aprovação/rejeição não são status extras da OS.

## Evidências

ES-01/ES-02; docs/ddd/regras-da-os.md; ServiceRequest.php


Atualização de implementação (03/11/2026): consulte os casos de uso em app/Application e os repositórios transacionais em app/Infrastructure/Persistence. A cadeia de PRs implementa OS, versões, decisão, estoque e indicador; Docker/MySQL executável permanece para o ambiente Windows.
