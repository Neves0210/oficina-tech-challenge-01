# Análise final de vulnerabilidades

Data: 03/11/2026. Foram repetidas a auditoria de dependências e a análise estática do código da aplicação na etapa Docker/Windows. Os arquivos auditados são identificados pelo [manifesto SHA-256](../qualidade/evidencias/windows-final/codigo.sha256.json).

## Auditoria de dependências

Comando: `docker compose exec -T app composer audit --locked --format=json`.

Resultado: código de saída 0; nenhum aviso conhecido e nenhum pacote abandonado na consulta. [JSON integral](../qualidade/evidencias/windows-final/composer-audit.json). A atualização anterior de symfony/mime para 8.1.10 permanece no composer.lock.

## Scan do código PHP

Foi executado Semgrep no container `semgrep/semgrep`, com a configuração `p/php`, métricas desativadas e escopo limitado a `app/` e `routes/`:

```powershell
docker run --rm -v "${PWD}:/src" semgrep/semgrep semgrep scan --config=p/php --metrics=off --disable-version-check --json --output /src/build/semgrep-private.json /src/app /src/routes
```

Resultado: 23 regras, 84 arquivos analisados, zero achados e zero erros. A versão da ferramenta está no [resumo publicável](../qualidade/evidencias/windows-final/semgrep-resumo.json). A imagem e o conjunto remoto de regras podem mudar em execuções futuras; o resumo registra o resultado desta execução, sem afirmar equivalência de scans posteriores.

## Controles e correções

Nenhuma nova vulnerabilidade foi identificada pelas ferramentas nesta etapa. Continuam verificados pelos testes: assinatura/expiração de JWT e papel administrador; validação e unicidade de documentos e placas; token do cliente restrito à OS, persistido como hash; versões imutáveis de orçamento; transições controladas; valores exatos; baixa atômica e rollback. A concorrência real no MySQL foi comprovada em três rodadas.

O ambiente local publica somente portas de loopback, mantém `.env` e relatórios com tokens fora do Git, usa usuário não root no PHP e desativa APP_DEBUG na API servida. As senhas padrão do banco no Compose são de desenvolvimento local. Uma implantação pública exige segredos próprios e configuração adequada do ambiente.

## Limites da análise

Composer audit identifica avisos publicados das dependências fixadas. Semgrep verifica os padrões cobertos pelas 23 regras aplicáveis; não realiza exploração dinâmica, scan completo das imagens ou pentest. Nenhum desses resultados garante ausência de vulnerabilidades desconhecidas. Não foram incluídos tokens, senhas locais ou respostas privadas nos relatórios publicados.
