# Análise de vulnerabilidades - versão consolidada

Atualização: [análise final Windows](fechamento-windows.md), com nova auditoria
Composer, scan Semgrep e concorrência MySQL validada. O texto abaixo é o registro anterior.

Consulta em 03/11/2026. Base de código `4f67f81954b6990a5506e3fece2501720115743a`.

Comando: `composer audit --locked --format=json`. Código de saída: 0.
[Resultado integral](evidencias/composer-audit-final.json): nenhum aviso de
vulnerabilidade conhecido e nenhum pacote abandonado na consulta.
A CI executa a mesma auditoria em Linux e Windows.

O aviso de symfony/mime 8.1.7 foi tratado no PR #4 com atualização para 8.1.10.
O [relatório dessa correção](analise-dependencias.md) preserva o histórico.

## Controles implementados e verificados

- JWT administrativo com verificação de assinatura, expiração e usuário administrador.
- CPF/CNPJ e placa validados e normalizados; sem autenticação baseada apenas em CPF.
- Token aleatório do cliente restrito à OS, validade de 24h, hash persistido,
  rotação/revogação e respostas sem cache. Decisão revalida a credencial na transação.
- Aprovação vigente e seis transições controladas; propostas antigas não autorizam execução.
- Baixa atômica, validação de todos os saldos, ordenação de bloqueios e restrição
  única de consumo; testes de rollback e repetição sem baixa duplicada.
- Valores monetários em centavos, quantidades exatas e validação de limites.
- Senha do administrador com hash; criação interativa; .env e saídas de build ignorados.

A evidência dessas regras está na [suíte e coleção](../qualidade/relatorio-final.md).
A análise de dependências cobre os avisos publicados dos pacotes fixados, sem
substituir pentest ou revisão completa do histórico Git. Não se afirma ausência
de vulnerabilidades desconhecidas. Concorrência real MySQL permanece pendente.
Antes de uma exposição pública do backend, configure HTTPS, APP_DEBUG=false e
segredos próprios no ambiente; o repositório público não exige hospedar a API.
