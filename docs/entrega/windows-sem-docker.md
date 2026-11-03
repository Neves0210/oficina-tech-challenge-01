# Execução e demonstração no Windows, sem Docker

Esta etapa prepara o backend local. Docker/Compose e validação em MySQL serão
feitos depois. Use PHP 8.4.1 ou superior, Composer, Git e Postman; habilite as
extensões verificadas por `composer check-platform-reqs`, incluindo pdo_sqlite.
Não é necessário Node.js para executar o backend.

## Obter a versão completa

Os PRs #5 a #12 foram integrados na main. Para obter a versão consolidada,
em uma pasta nova no PowerShell:

```powershell
git clone --branch main https://github.com/Neves0210/oficina-tech-challenge-01.git oficina-tech-challenge
Set-Location oficina-tech-challenge
php --version
composer install
composer check-platform-reqs
Copy-Item .env.example .env
php artisan key:generate
php artisan jwt:secret
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan app:create-admin
php artisan serve --host=127.0.0.1 --port=8000
```

A cópia nova evita sobrescrever um .env já configurado. A conta administrativa
é criada com nome, e-mail e senha digitados pelo integrante; não há senha padrão.
Não é preciso publicar uma API na internet para cumprir a execução local.
Antes da entrega, fixe o commit usado na avaliação e no vídeo.

## Postman

Importe `docs/postman/oficina-cadastros.postman_collection.json`.
Crie um ambiente local com `base_url=http://127.0.0.1:8000`, `admin_email` e
`admin_password` correspondentes ao administrador criado. Mantenha senha e
valores de tokens no ambiente local, sem exportar ou versionar valores reais.
A coleção obtém e salva o JWT em `access_token` automaticamente e usa uma
credencial diferente para o cliente. Execute a coleção inteira na ordem original.

A validação registrada executou 69 chamadas e 93 asserções. As etapas cobrem
cadastros, estoque, OS, revisão e versões de orçamento, decisão do cliente,
execução, finalização, entrega e indicador. A duração pode ser zero em uma demo
rápida, o que é válido. Para mostrar um intervalo maior, aguarde entre início
e finalização.

A coleção cria dados fictícios com documentos e placas fixos e preserva o
histórico de OS; ela não limpa todo o banco ao terminar. Use uma cópia/banco
exclusivo de demonstração e vazio na primeira execução. Repetir em um banco já
preenchido pode gerar conflitos de unicidade; isso não comprova defeito da API.
Não execute comandos de limpeza em banco com informações que deseja manter.

## Testes e cobertura

Em outro PowerShell na pasta do projeto:

```powershell
composer test
composer validate --strict
composer audit --locked
```

Para cobertura, configure Xdebug em modo coverage ou PCOV e rode:

```powershell
$env:XDEBUG_MODE = "coverage"
composer test:coverage
```

A variável sozinha não instala nem habilita a extensão. O comando exige 80%
global e por domínio executável, e grava HTML/Clover/JUnit em build/.
Os testes padrão usam SQLite em memória e não o banco da demonstração.

A CI passou também em Windows numa pasta com acento, incluindo a entrada real
pelo console. Esse resultado não substitui conferir PHP, extensões e Postman
instalados no PC usado para gravar o vídeo.

## Conferência antes da gravação

Verifique a API em `/api/openapi`, um token administrativo válido, o Runner
Postman sem falhas e os PDFs DDD acessíveis. Registre a versão demonstrada:

```powershell
git branch --show-current
git rev-parse HEAD
git status --short
```

Depois da etapa Docker, a gravação final deve mostrar a forma de execução
exigida e usar o mesmo commit da entrega. Não mostre senhas nem tokens no vídeo.
