# ADR 001 - Monólito em camadas

Data: 03/11/2026 (America/Sao_Paulo).

## Situação

Existente no código; formalização documental nesta data. Registro das decisões já descritas no projeto; não inventa aprovação coletiva.

## Contexto

O MVP exige backend monolítico e manutenção simples.

## Decisão

Manter uma aplicação Laravel, separando HTTP, aplicação, domínio e infraestrutura. O domínio define contratos e não importa Eloquent.

## Alternativas consideradas

Camadas convencionais com regras nos controllers; arquitetura distribuída. A primeira aumenta acoplamento; a segunda amplia operação e foge do monólito exigido.

## Consequências

Uma implantação e transações locais simples; a equipe deve manter a direção das dependências. Pastas sozinhas não garantem um domínio bem modelado.

## Evidências

D02/D03; app/Providers/AppServiceProvider.php; docs/ddd/arquitetura-e-banco.md


Atualização de implementação (03/11/2026): consulte os casos de uso em app/Application e os repositórios transacionais em app/Infrastructure/Persistence. A cadeia de PRs implementa OS, versões, decisão, estoque e indicador; Docker/MySQL executável permanece para o ambiente Windows.
