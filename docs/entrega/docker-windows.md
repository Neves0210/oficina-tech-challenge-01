# Execução final com Docker no Windows

O ambiente de avaliação contém PHP 8.4, Laravel 13, Composer, PCOV, Nginx e MySQL 8.4. A imagem inclui o código e as dependências fixadas no composer.lock. Não é necessário instalar PHP, Composer ou Node para executar a API e os testes. Docker Desktop deve estar aberto, em modo Linux, com WSL 2 habilitado.

## Primeira execução

Na pasta do repositório, execute no PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1
docker compose exec app php artisan app:create-admin
```

O script cria `.env` se necessário, gera apenas chaves vazias e preserva as existentes, constrói a imagem, aguarda os serviços e aplica migrations. A senha administrativa é digitada interativamente. Não há conta ou senha administrativa padrão na imagem.

- Saúde: http://127.0.0.1:8081/up
- OpenAPI: http://127.0.0.1:8081/api/openapi
- API administrativa: http://127.0.0.1:8081/api
- MySQL Workbench: host `127.0.0.1`, porta `3308`, schema `oficina`, usuário `oficina`. A senha local é a configurada em `DOCKER_DB_PASSWORD`; o padrão do Compose serve apenas ao desenvolvimento.

As portas `8081` e `3308` evitam colisão com o protótipo anterior. Para alterar, defina `APP_PORT` e `MYSQL_HOST_PORT` no `.env`. As variáveis `DOCKER_DB_DATABASE`, `DOCKER_DB_USERNAME`, `DOCKER_DB_PASSWORD` e `DOCKER_MYSQL_ROOT_PASSWORD` configuram o MySQL Docker sem depender de um `.env` antigo com SQLite. Mudanças de senha não alteram usuários de um volume já inicializado.

O código é copiado para a imagem. Após alterar código, execute novamente o script para reconstruir. O volume MySQL preserva os dados; `docker compose down` para os serviços sem apagar o volume. A configuração é para avaliação local, com portas limitadas a loopback.

## Testes isolados e cobertura

```powershell
docker compose run --rm tests
docker compose run --rm -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: tests composer test:coverage
docker compose run --rm tests php scripts/verify-mysql-concurrency.php
docker compose exec -T app composer audit --locked
```

O serviço `tests` usa exclusivamente `oficina_testing`, cache e sessões em memória. Nunca execute a suíte com a conexão de desenvolvimento. `RefreshDatabase` recria o schema do banco de testes. A inicialização do MySQL cria esse banco separado e concede acesso ao usuário local. Em um volume criado antes desse script, crie o banco de testes explicitamente sem apagar seus dados.

O teste concorrente executa três rodadas com duas conexões independentes, sobreposição comprovada e bloqueio real do saldo. Cada rodada exige um sucesso, uma recusa por estoque insuficiente, saldo zero, uma única saída e rollback da OS recusada. Ele se recusa a rodar fora de `APP_ENV=testing`, MySQL e schema `oficina_testing`.

Cobertura HTML: `build/coverage/html/index.html`. Relatório Clover: `build/coverage/clover.xml`. JUnit: `build/junit.xml`. PCOV já está habilitado na imagem; os mínimos global e por domínio continuam em 80%.

## Demonstração com Postman

Importe a coleção `docs/postman/oficina-cadastros.postman_collection.json`. Crie um ambiente com `base_url=http://127.0.0.1:8081`, `admin_email` e `admin_password` da conta criada. Se preferir gerar uma conta aleatória para demonstração:

```powershell
docker compose exec -T app php scripts/prepare-demo.php
```

Importe `build/postman-local.environment.json` e selecione esse ambiente. O arquivo contém a senha local: mantenha-o apenas neste computador, fora do Git e do vídeo. Ele não é necessário para a avaliação, pois o avaliador pode criar a própria conta.

Execute a coleção em ordem, em um banco de demonstração dedicado e inicialmente vazio. São 69 chamadas e 93 asserções. Ela preserva históricos e usa documentos/placas fixos; repetir toda a coleção no mesmo banco pode causar conflitos de unicidade. Não apague um banco com dados que deseja manter. Para ensaios repetidos, use um novo projeto Compose e um novo volume, por exemplo `docker compose -p oficina-ensaio2 up -d --build --wait`, após parar o ambiente anterior para liberar as portas. Use o mesmo `-p oficina-ensaio2` em todos os comandos desse ensaio.

## Conferência e versão

```powershell
docker compose ps
git branch --show-current
git rev-parse HEAD
git status --short
```

Use o mesmo commit no vídeo e no PDF. O código e os documentos públicos devem permanecer disponíveis durante a correção. A gravação e a publicação no YouTube são feitas pelo integrante.
