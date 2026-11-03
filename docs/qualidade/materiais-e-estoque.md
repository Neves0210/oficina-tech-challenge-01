# Validação de peças, insumos e estoque

Execução em 03/11/2026, PHP 8.5.8 e SQLite, sem Docker.

- Suíte PHPUnit: 64 testes aprovados e 1 ignorado (regressão específica de caminhos Windows com acentos), 341 asserções.
- Cobertura de linhas de `app/`: 348/357, ou 97,48%, com PCOV 1.0.12.
- Novas verificações: isolamento entre tipos, CRUD, entrada/saída, precisão decimal, quantidade inteira de peças, validação de dados, autenticação, insuficiência de saldo, overflow, histórico e rollback após falha na gravação da movimentação.
- OpenAPI validada com Swagger Parser, incluindo resolução das referências internas.
- Coleção Postman executada com Newman contra PHP HTTP em localhost e banco temporário dedicado: 33 requisições e 40 asserções, zero falhas. A coleção inclui os cadastros anteriores e os novos materiais.
- Pint e `git diff --check` passaram.

O Postman CLI local apresentou falha no acesso ao Keychain e encerrou a
validação; por isso a evidência HTTP foi obtida com Newman, sem login no Postman.
Nenhum dado ou credencial de produção foi utilizado.

Reprodução da suíte: `composer test:coverage` com PCOV ou Xdebug habilitado.
Para repetir a coleção, use um banco exclusivo, crie o administrador pelo Artisan
e informe `base_url`, `admin_email` e `admin_password` em ambiente local. O histórico
dos materiais criados pela demonstração é preservado e eles são inativados.

A cobertura não contempla módulos ainda ausentes. Testes de concorrência e
bloqueio no MySQL permanecem pendentes até esse ambiente estar disponível.
