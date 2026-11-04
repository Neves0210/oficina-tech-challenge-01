# Oficina Tech Challenge — Fase 1

Repositório do MVP de backend da oficina mecânica para o Tech Challenge da FIAP.

A implementação atual oferece APIs administrativas de clientes, veículos, serviços, peças e insumos, protegidas por JWT, com validação de CPF/CNPJ e placa. Peças e insumos têm saldo e histórico de entradas/saídas em transação. O atendimento inclui abertura e diagnóstico de OS, orçamento versionado, decisão do cliente e execução com baixa atômica de materiais até finalização e entrega. O cliente acompanha a própria OS por credencial específica. A API administrativa também informa o tempo médio das OS concluídas.

Pré-requisitos: PHP 8.4.1 ou superior, Composer e as extensões indicadas por `composer check-platform-reqs`. O banco escolhido para a solução é MySQL; a execução rápida e os testes locais abaixo usam SQLite, que exige `pdo_sqlite`.

## Execução recomendada com Docker

No Windows, com Docker Desktop aberto e containers Linux, não é necessário instalar PHP ou Composer. Na pasta deste repositório:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1
docker compose exec app php artisan app:create-admin
```

API: `http://127.0.0.1:8081/api`. OpenAPI: `http://127.0.0.1:8081/api/openapi`. Saúde: `http://127.0.0.1:8081/up`. O script gera somente chaves ausentes, preserva as existentes, constrói a imagem e aplica migrations. MySQL persiste em volume; a imagem inclui o código e as dependências. Após alterações, reconstrua a imagem.

```powershell
docker compose run --rm tests
docker compose run --rm -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: tests composer test:coverage
docker compose run --rm tests php scripts/verify-mysql-concurrency.php
docker compose exec -T app composer audit --locked
```

O serviço de testes usa banco MySQL separado e cache em memória. [Guia Docker e Postman](docs/entrega/docker-windows.md). [Validação final Windows/MySQL](docs/qualidade/fechamento-windows.md). [Análise final de vulnerabilidades](docs/seguranca/fechamento-windows.md). [Vídeo da entrega](docs/entrega/video.md).

Arquitetura: monólito em camadas, com HTTP, aplicação, domínio e infraestrutura. Tecnologias: PHP 8.4+, Laravel 13, JWT, MySQL 8.4, PHPUnit, PCOV, Nginx e Docker Compose. MySQL foi escolhido por integridade referencial, transações e bloqueios de linha para coordenar OS e estoque; veja o [ADR do banco](docs/arquitetura/adr/002-banco-relacional.md).

## Alternativa local sem Docker

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

Para medir a cobertura, habilite PCOV ou Xdebug em modo coverage e execute `composer test:coverage`. O comando exige pelo menos 80% de cobertura de linhas do código de `app/` e de cada domínio com linhas executáveis em `app/Domain/`, e gera HTML, Clover e JUnit em `build/`. A medição considera apenas código implementado; módulos futuros não entram no denominador.

O checkout da CI usa a pasta `oficina-avaliação` para exercitar também a regressão de caminho com acentos no Windows. O GitHub Actions executa testes e cobertura em Linux com PHP 8.4/8.5 e Windows com PHP 8.5, além de validar o Composer e auditar as dependências. Os relatórios ficam nos artefatos de cada execução por 14 dias. O fluxo não usa Docker nem credenciais do banco de desenvolvimento.

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

## Documentação e evidências

- [Guia Windows sem Docker e demonstração Postman](docs/entrega/windows-sem-docker.md)
- [Requisitos, implementação e pendências](docs/arquitetura/requisitos-e-rastreabilidade.md)
- [Event Storming dos três fluxos](docs/ddd/event-storming.md) e [PDF](docs/ddd/event-storming.pdf)
- [Diagramas, contextos, entidades e objetos de valor](docs/ddd/catalogo-de-diagramas.md) e [PDF](docs/ddd/diagramas.pdf)
- [Situação do compartilhamento e FigJam histórico](docs/ddd/indice-quadro.md)
- [Relatório de testes, cobertura e evidências permanentes](docs/qualidade/relatorio-final.md)
- [Análise de vulnerabilidades consolidada](docs/seguranca/relatorio-final.md)
- [Integração dos PRs e fechamento da entrega](docs/entrega/estado-e-proximos-passos.md)

Docker/Compose, execução MySQL, fluxo HTTP e concorrência foram validados no Windows.
Os PDFs de DDD são anexos de apoio. O PDF acadêmico com campos editáveis e o roteiro
de 14m30s são gerados por `scripts/build-delivery-pdfs.py`; o procedimento para
inserir o link real do vídeo está em [finalização da entrega](docs/entrega/finalizar-pdf.md).
O vídeo ainda deve ser gravado e publicado pelo integrante.
