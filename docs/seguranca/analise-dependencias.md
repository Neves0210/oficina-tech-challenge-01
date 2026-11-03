# Análise das dependências PHP

Consulta em 03/11/2026. Escopo: pacotes fixados no `composer.lock` desta alteração.

`symfony/mime` foi atualizado de v8.1.7 para v8.1.10 para corrigir o aviso
[CVE-2026-45070](https://symfony.com/cve-2026-45070), de severidade média,
sobre injeção em cabeçalhos de e-mail. Nenhuma outra dependência teve a versão
alterada.

Comando: `composer audit --locked --format=json`.

Resultado: código de saída zero, sem avisos de vulnerabilidade e sem pacotes
abandonados. O [JSON da consulta](evidencias/composer-audit.json) está versionado.
Essa auditoria não é um pentest nem uma garantia de ausência de falhas no código.
Cada execução do GitHub Actions refaz a consulta e guarda seu próprio resultado.

Validação local com PHP 8.5.8 e PCOV 1.0.12: 33 testes aprovados, uma regressão
específica de Windows ignorada no macOS, 182 asserções e 96,47% de cobertura
de linhas de `app/` (246/255). A cobertura mede apenas os módulos existentes.

O lock já utiliza componentes Symfony que exigem PHP 8.4.1; a restrição do
Composer e o README foram alinhados a esse mínimo. Os jobs de Linux e Windows
validarão as plataformas do workflow. A preparação de Docker permanece adiada.
