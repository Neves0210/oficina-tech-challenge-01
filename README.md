# Oficina Tech Challenge — Fase 1

Repositório do MVP de backend da oficina mecânica para o Tech Challenge da FIAP.

A implementação atual oferece APIs administrativas de clientes, veículos, serviços, peças e insumos, protegidas por JWT, com validação de CPF/CNPJ e placa. Peças e insumos têm saldo e histórico de entradas/saídas em transação. O atendimento inclui abertura e diagnóstico de OS, orçamento versionado, decisão do cliente e execução com baixa atômica de materiais até finalização e entrega. O cliente acompanha a própria OS por credencial específica.

Pré-requisitos: PHP 8.4.1 ou superior, Composer e as extensões indicadas por `composer check-platform-reqs`. O banco escolhido para a solução é MySQL; a execução rápida e os testes locais abaixo usam SQLite, que exige `pdo_sqlite`.

Para preparar uma cópia nova do repositório:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan jwt:secret
```

No macOS, use `cp .env.example .env` no lugar de `Copy-Item`. Preserve seu `.env` se ele já existir. Para SQLite, crie o arquivo antes da migration:

```shell
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan app:create-admin
php artisan serve
```

A API fica em `http://localhost:8000`. Não há interface visual. A conta administrativa é criada interativamente, sem credenciais padrão. Gere o token com e-mail e senha em `POST /api/auth/token` e envie o JWT como Bearer nas chamadas administrativas. Não versione `.env` nem chaves JWT.

Para MySQL, configure `DB_CONNECTION=mysql`, host, porta, nome, usuário e senha no `.env` antes das migrations. Use um banco dedicado ao projeto; para testes, use outro banco exclusivo.

```shell
composer check-platform-reqs
php artisan test
```

Os testes padrão usam SQLite em memória e não alteram seu banco de desenvolvimento.

Para medir a cobertura, habilite PCOV ou Xdebug em modo coverage e execute `composer test:coverage`. O comando exige pelo menos 80% de cobertura de linhas do código de `app/` e gera HTML, Clover e JUnit em `build/`. A medição considera apenas código implementado; módulos futuros não entram no denominador.

O GitHub Actions executa testes e cobertura em Linux com PHP 8.4/8.5 e Windows com PHP 8.5, além de validar o Composer e auditar as dependências. Os relatórios ficam nos artefatos de cada execução por 14 dias. O fluxo não usa Docker nem credenciais do banco de desenvolvimento.

- [Referência dos cadastros e autenticação](docs/api-cadastros.md)
- [Peças, insumos e movimentações de estoque](docs/api-materiais.md)
- [Abertura e diagnóstico de ordens de serviço](docs/api-ordens.md)
- [Orçamentos versionados](docs/api-orcamentos.md)
- [Acompanhamento e decisão do cliente](docs/api-cliente.md)
- [Execução, finalização e entrega](docs/api-execucao.md)
- [Tempo médio de execução](docs/api-indicadores.md)
- [Especificação OpenAPI](docs/openapi.json), também disponível em `GET /api/openapi`

- [Linguagem ubíqua](docs/ddd/linguagem-ubiqua.md)
- [Regras da ordem de serviço](docs/ddd/regras-da-os.md)
- [Modelo de dados](docs/ddd/modelo-de-dados.md)
- [Arquitetura e justificativa do banco](docs/ddd/arquitetura-e-banco.md)

Organização: `app/Http` contém a interface HTTP, `app/Application` os casos de uso de cadastro, `app/Domain` as regras e contratos, `app/Infrastructure` os adaptadores de persistência e JWT, `database/migrations` o esquema e `tests` as verificações unitárias e de integração.
