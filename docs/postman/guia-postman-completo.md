# Guia completo do Postman para a apresentação

Gabriel De Barros Neves | rm378693 | Grupo 19 | Discord Nev3s

Este guia acompanha o PDF de entrega de 7 páginas enviado pelo integrante. Ele explica o preparo do ambiente, a ordem dos cliques, as rotas, os JSONs e os resultados. A leitura e o ensaio são feitos antes da gravação; durante o vídeo, use o roteiro de 14m30s e mostre respostas representativas.

## O que importar

- `oficina-fluxo-guiado.postman_collection.json`: sequência de 70 requisições para executar em ordem. Ela inclui serviço, peça e insumo na mesma OS, versões de orçamento, rejeição, aprovação e entrega.
- `oficina-referencia-completa.postman_collection.json`: catálogo das 53 operações da OpenAPI, mais saúde e a própria especificação, totalizando 55 entradas. Use para consultar e enviar uma rota individualmente, conforme o estado do banco. Não rode essa coleção inteira como um fluxo.
- `oficina-docker.postman_environment.json`: ambiente público com apenas base_url e os campos vazios de email e senha administrativa. Os IDs e tokens são preenchidos nos valores locais da coleção guiada.
- `openapi.json`: especificação completa, com schemas, obrigatoriedade dos campos e respostas documentadas. Importá-la é opcional porque as duas coleções já estão prontas.

O fluxo guiado é uma versão para apresentação derivada da coleção anterior de 69 chamadas. Foi acrescentada a reativação da peça e a inclusão dela na OS. O total demonstrado passa de R$ 62,50 para **R$ 87,50**. Os números antigos continuam sendo evidências da coleção anterior, não o total desta nova execução.

Validação da nova coleção: **70 requisições e 94 asserções aprovadas**, sem falhas, contra MySQL em um projeto Docker separado. A OS abriu com três tipos de item e total de 8750 centavos, entrou em execução e chegou a Entregue. O resumo publicável está em `validacao-fluxo-guiado.json`.

## 1 Preparar a API no Windows

Abra o Docker Desktop. No PowerShell, entre na pasta `oficina-tech-challenge-01`, que contém `artisan` e `docker-compose.yml`, e execute:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1
docker compose exec app php artisan app:create-admin
```

O primeiro comando constrói a aplicação, inicia os serviços e aplica migrations. O segundo pergunta nome, email e senha de pelo menos 12 caracteres. Guarde os valores que você digitou para preencher o ambiente do Postman. Não coloque o CPF do cliente como login administrativo.

Abra `http://127.0.0.1:8081/up`: deve retornar HTTP 200. Depois abra `http://127.0.0.1:8081/api/openapi`: deve aparecer a especificação JSON. Se a porta foi alterada no .env, ajuste base_url no Postman para a mesma porta.

O banco da gravação deve estar vazio antes da primeira execução do fluxo. A coleção usa CPF, CNPJ e placas fixos e preserva o histórico da OS. Rodar novamente no mesmo banco pode gerar 422 por duplicidade. Fechar o Postman ou reiniciar containers não apaga o banco.

Para um novo ensaio sem apagar os dados existentes, pare o ambiente anterior e crie um projeto Compose separado:

```powershell
docker compose stop
docker compose -p oficina-gravacao-01 up -d --build --wait
docker compose -p oficina-gravacao-01 exec -T app php artisan migrate --force
docker compose -p oficina-gravacao-01 exec app php artisan app:create-admin
```

Use `-p oficina-gravacao-01` em todos os comandos desse ambiente. Para outro banco novo, escolha outro nome, como oficina-gravacao-02, após parar o projeto anterior. Não execute limpeza destrutiva no seu banco principal. A conta administrativa também pertence ao novo banco: crie-a novamente nesse projeto.

## 2 Importar os arquivos e selecionar o ambiente

1. Extraia o ZIP recebido para uma pasta local.
2. No Postman Desktop, abra Import e selecione os dois arquivos de coleção e o arquivo de ambiente. O ZIP contém também documentação; importe somente os JSONs de coleção e ambiente nesse momento.
3. Abra Environments e selecione `Oficina Docker - preencher credenciais localmente`.
4. Confira base_url com o valor `http://127.0.0.1:8081`, sem barra final e sem acrescentar /api. As rotas já incluem /api.
5. Preencha admin_email e admin_password com a conta criada pelo Artisan. Use os valores locais, não compartilhe a senha, e marque-a como sensível quando a interface oferecer essa opção.
6. Selecione esse ambiente no seletor de ambientes do Postman antes de enviar requisições ou iniciar o Runner.

Não crie access_token, customer_token ou IDs vazios no ambiente. Os scripts usam `pm.collectionVariables.set`, e uma variável de ambiente com o mesmo nome tem precedência sobre a variável da coleção. Isso pode esconder o valor salvo e causar 401 ou URL com ID vazio.

## 3 Entender autenticação cabeçalhos e variáveis

O login usa No Auth. As chamadas administrativas herdam Bearer Token da coleção com `{{access_token}}`. As chamadas em `/api/customer/` usam Bearer Token `{{customer_token}}`, configurado na própria requisição. A troca entre administrador e cliente já está pronta: não substitua todos os tokens manualmente.

As requisições enviam `Accept: application/json`. Quando existe corpo, ele está em Body, raw, JSON, com `Content-Type: application/json`. GET, DELETE e ações sem payload ficam com Body = none. Em uma chamada sem corpo, a presença do Content-Type não exige que você digite `{}`.

O JWT administrativo desta versão dura **3600 segundos**, ou uma hora. Se expirar, envie novamente apenas a requisição 001 de login; isso não recria os cadastros nem reinicia a coleção. O token do cliente dura 24 horas e é específico da OS; emitir outra credencial para a mesma OS substitui a anterior.

IDs e tokens ficam em Collection Variables, nos valores locais da coleção guiada. Eles são salvos após os cadastros e usados nas próximas URLs. Não copie números fixos para substituir as variáveis: o ID real depende do banco.

As coleções possuem escopos separados. Para testar uma chamada no catálogo de referência após executar o fluxo guiado, preencha na coleção de referência o ID correspondente e os tokens locais, ou envie novamente o login e use as listagens para escolher os IDs. Não mova esses dados para um ambiente público.

## 4 Enviar uma chamada manualmente

Abra a coleção guiada e a primeira pasta. Selecione `001 | Obter token administrativo`. Confira o método POST, a URL e o corpo. Envie em Send. Espere HTTP 200 e o teste Status 200 aprovado. O script guarda access_token automaticamente; não é necessário copiá-lo da resposta.

Para cada chamada seguinte, abra a requisição pelo número, confira Body quando houver, clique em Send e leia o status. Em Test Results ou Scripts/Post-response, confira as asserções. O nome da aba varia conforme a versão do Postman. Os scripts completos já estão dentro do JSON importado.

Na chamada 002, por exemplo, o CPF com máscara deve voltar normalizado como `52998224725`, e customer_id será salvo. A chamada 004 usa esse ID ao cadastrar o veículo. Isso demonstra o encadeamento dos dados, sem preencher IDs manualmente.

## 5 Executar pelo Runner

Selecione a coleção guiada e abra Run. Nas versões que mostram essas opções, escolha Functional e Local. Selecione o ambiente preenchido, deixe uma iteração, sem arquivo de dados e sem reordenar as requisições. Execute em Start run.

Ative Keep variable values para manter os IDs e tokens locais ao terminar, se quiser abrir consultas depois. Para ver os corpos das respostas no resultado do Runner, mantenha a opção de persistir respostas na sessão habilitada. Não mostre respostas com senha ou token no vídeo.

Você pode executar uma pasta de cada vez na gravação, sempre em ordem: 01 Cadastros, 02 Materiais, 03 Abertura e diagnóstico, 04 Orçamentos, 05 Cliente, 06 Execução e entrega e 07 Indicadores. Preserve os valores locais entre as pastas. Se um cadastro obrigatório falhar, pare e resolva antes de avançar.

Uma resposta 409, 422 ou 404 pode ser exatamente o resultado esperado de um teste negativo. A execução está correta quando as asserções esperam esse status e passam. Não espere HTTP 200 em todas as linhas.

## 6 O que mostrar em cada minuto do vídeo

### 00m00s a 00m40s Identificação e problema

Abra a página 1 do novo PDF. Diga seu nome, RM, Grupo 19 e Discord Nev3s. Explique que o backend organiza o atendimento e evita perda de histórico, descontrole de estoque e autorizações informais. Cite PHP, Laravel, MySQL, JWT e Docker.

### 00m40s a 02m05s Arquitetura

Use a página 2 do PDF e o projeto no editor. Mostre Http, Application, Domain e Infrastructure. Abra OrderStatus para exemplificar uma regra e EloquentExecutionRepository para mostrar a transação. Explique a escolha do MySQL por relacionamentos, integridade referencial e bloqueio de saldo.

### 02m05s a 03m40s DDD

Use a página 3 do PDF e o quadro indicado nela. Mostre criação da OS, acompanhamento/execução e gestão de materiais. Relacione ator, comando, evento e política. Cite OS como raiz, orçamento versionado, contextos lógicos e objetos de valor DocumentNumber, Plate, Money e Quantity. Compare o quadro com os diagramas versionados antes de gravar; os elementos apresentados devem corresponder à implementação.

### 03m40s a 04m30s Execução e documentação

No terminal, mostre `docker compose ps` ou o equivalente com -p do ambiente escolhido. Abra GET /up e GET /api/openapi. O primeiro retorna 200; o segundo descreve a API. Se quiser uma visualização navegável, importe openapi.json no Postman antes da gravação. Não perca tempo compilando a imagem ao vivo.

### 04m30s a 06m20s Autenticação e cadastros

Execute a pasta 01, chamadas 001 a 013, e a pasta 02, chamadas 014 a 033. Mostre o login sem revelar o token, o cliente cadastrado com CPF normalizado e o vínculo do veículo. Destaque 006 = 422 por CPF duplicado e 007 = 409 por vínculo existente. Mostre entradas/saídas, histórico, peças inteiras e insumos fracionados. As exclusões 010 a 012 limpam apenas os cadastros iniciais; os cadastros usados pela OS são criados depois.

Fala: “O administrador se autentica por JWT. Os cadastros são validados e os IDs seguem automaticamente para as próximas chamadas. O estoque é movimentado com motivo, autor e histórico; a API recusa saldo insuficiente.”

### 06m20s a 09m20s OS orçamento e entrega

Execute as pastas 03 a 06 em ordem. Abra especialmente 039, 040, 043, 053, 056, 058, 062, 064, 066 e 067. A 039 cria a OS com serviço, peça e insumo e total de 8750 centavos. A 040 registra o diagnóstico. A 043 apresenta o orçamento. A 053 rejeita a proposta; 055 e 056 revisam e apresentam a versão 3. A 058 aprova a versão vigente. A 062 recusa execução sem insumo. A 064 inicia com aprovação e saldo. A 066 finaliza e a 067 entrega.

Fala: “A OS nasce Recebida, entra em diagnóstico e aguarda aprovação quando a proposta é apresentada. O orçamento soma R$ 50 de serviço, R$ 25 de peça e R$ 12,50 de insumo, totalizando R$ 87,50. Rejeitar preserva o histórico e exige revisão. Aprovar registra a decisão; iniciar a execução é outra ação, que valida o saldo e baixa os materiais atomicamente. Depois temos Finalizada e Entregue.”

### 09m20s a 10m30s Regras negativas

Abra os resultados já obtidos: 046 = orçamento duplicado recusado; 057 = decisão sobre versão antiga recusada; 059 = decisão repetida recusada; 062 = execução sem saldo recusada; 065 = início repetido recusado. Todos esperam 409. Não reenvie a pasta inteira, porque o estado da OS já avançou.

Fala: “Essas respostas são proteções do negócio. Não é possível executar uma proposta antiga, decidir duas vezes, usar estoque inexistente ou consumir o mesmo material duas vezes.”

### 10m30s a 11m20s Consulta e indicador

Mostre a consulta administrativa da OS e a chamada 068 do cliente com status Entregue. Na 069, destaque a única saída de 0,500 L ligada à OS. Envie 070 para o indicador de tempo médio. O resultado pode ser próximo de zero se o Runner finalizou tudo rapidamente; o cálculo considera início até finalização, excluindo a espera pela retirada.

### 11m20s a 13m25s Qualidade e segurança

Abra a página 5 do PDF, os relatórios e a cobertura HTML. Execute a suíte MySQL e a verificação concorrente pelo terminal; mostre Composer audit e o resumo Semgrep. Distinga a evidência histórica de 69/93 da nova coleção guiada de 70 chamadas. Não apresente os testes HTTP do Postman como substitutos da cobertura PHPUnit.

### 13m25s a 14m30s Fechamento

Use as páginas 6 e 7 do novo PDF como checklist. Mostre o repositório, a integração por PR, a branch e o hash. Informe que essa é a versão demonstrada. Encerre até 14m30s para ter margem até o limite de 15 minutos. Depois de gravar, confirme as minutagens reais e preencha o link público do vídeo no PDF de entrega.

## 7 Sequência de estado e valores que você deve reconhecer

- 039: Recebida, três itens, total 8750 centavos.
- 040: Em diagnóstico.
- 043: proposta v1 apresentada, OS Aguardando aprovação.
- 047: revisão retorna ao diagnóstico; 048 cria v2 PENDING.
- 053: rejeita v2; a OS continua Aguardando aprovação.
- 055 e 056: revisão e proposta v3 PENDING.
- 058: orçamento APPROVED; a execução ainda não começou.
- 061: retira 1,5 L restantes para provocar falta de insumo.
- 062: 409; o início e as baixas devem ser revertidos.
- 063: entra 1 L.
- 064: Em execução; peça passa de 1 para 0 unidade e insumo passa de 1 para 0,5 L.
- 065: 409, sem nova baixa.
- 066: Finalizada; 067: Entregue; 068: cliente vê Entregue.
- 069: uma saída de 0,500 L vinculada à OS; 070: média das OS concluídas.

## 8 Se algo der errado

- ECONNREFUSED ou Could not get response: confira Docker Desktop, serviços, porta e base_url; teste /up. Se estiver no Postman Web, utilize um agente local adequado para alcançar localhost ou prefira o aplicativo Desktop.
- 401 no login: confira email, senha e se a conta foi criada no mesmo projeto/banco que a API está usando.
- 401 nas rotas administrativas: envie 001 novamente; confira herança de autenticação e se não existe access_token vazio no ambiente.
- 401 ou 403 no cliente: confira customer_token e order_id; a credencial é específica da OS e pode ter sido revogada, substituída ou expirada. A implementação usa recusa de acesso do cliente; consulte a mensagem recebida.
- URL com variável vazia/vermelha: execute o passo que cria o ID e veja Collection Variables. Não invente um ID nem escreva {{id}} onde a coleção usa {{order_id}}.
- 422 inesperado em cadastro: confira o JSON e os campos de errors. Em nova execução, CPF/CNPJ/placa podem já existir; use um banco de ensaio novo.
- 409 inesperado: confira o estado atual da OS, a versão do orçamento e o saldo. Não force status no corpo JSON; use as ações específicas.
- 429: limite de requisições atingido; aguarde a janela de um minuto. Login e emissão de credencial têm limite de 10 por minuto.
- HTTP 204: sucesso sem corpo; não tente abrir data.id na resposta.
- HTTP 500: veja os logs com `docker compose logs --tail=100 app web mysql`, usando -p se estiver em outro projeto. Não mostre credenciais ou logs privados no vídeo.
- Teste JavaScript falhou depois de um 422: o script pode ter tentado ler data.id que não existe porque o cadastro falhou. Resolva o primeiro erro, não apenas o erro do script.

## 9 Regras para editar JSON e consultar outras rotas

Valores monetários são inteiros em centavos: 5000 = R$ 50,00. Não envie price_cents nos itens da OS: a API copia os preços do catálogo. O tipo do item é SERVICE, PART ou SUPPLY; catalog_id deve apontar ao recurso do tipo correto. quantity é inteira para serviço/peça e aceita até três casas para insumo. Não altere stock_quantity diretamente no cadastro; use stock-movements.

PUT envia todos os campos obrigatórios do recurso. PATCH permite alteração parcial. Nas listagens, use `?page=1&per_page=20`; per_page vai de 1 a 100 e page de 1 a 1000000. GET, PUT, PATCH e DELETE que usam IDs devem apontar a registros existentes. Exclusões com vínculos ou histórico podem retornar 409.

PUT /api/service-orders/{{order_id}}/items substitui todos os itens e só funciona enquanto a OS é editável, em Recebida ou Em diagnóstico. Use antes de apresentar a proposta, ou após uma revisão autorizada. A rota DELETE /api/service-orders/{{order_id}}/customer-access revoga a credencial do cliente; use apenas como demonstração adicional, fora da sequência principal, para não interromper as consultas do vídeo.

## Referências oficiais da interface

Importação: https://learning.postman.com/docs/getting-started/importing-and-exporting/importing-data/

Ambientes e precedência: https://learning.postman.com/docs/use/send-requests/variables/managing-environments

Runner: https://learning.postman.com/docs/tests-and-scripts/running-collections/intro-to-collection-runs/

Os nomes dos controles podem variar entre versões; os arquivos fornecidos usam o formato de coleção Postman v2.1.

# Catálogo dos 70 passos em ordem

Os números abaixo são os mesmos dos nomes no Postman. Cabeçalhos JSON e seleção do ambiente seguem a preparação. As verificações listadas são os testes efetivamente configurados.

## 01 Cadastros iniciais

### 001 | Obter token administrativo

**Método e URL:** `POST {{base_url}}/api/auth/token`

**Autenticação:** No Auth.

**Antes de enviar:** admin_email, admin_password devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Autentique a conta administrativa criada pelo Artisan. access_token é salvo automaticamente; o JWT desta versão dura 3600 segundos. Não exponha o valor no vídeo.

**Body:** raw / JSON.

```json
{
  "email": "{{admin_email}}",
  "password": "{{admin_password}}"
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** access_token.


### 002 | Cadastrar cliente

**Método e URL:** `POST {{base_url}}/api/customers`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.

**Body:** raw / JSON.

```json
{
  "name": "Marcos Silveira",
  "document": "529.982.247-25",
  "email": "cliente@example.test"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; CPF normalizado.

**Variáveis salvas:** customer_id.


### 003 | Consultar cliente

**Método e URL:** `GET {{base_url}}/api/customers/{{customer_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 004 | Cadastrar veículo do cliente

**Método e URL:** `POST {{base_url}}/api/vehicles`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.

**Body:** raw / JSON.

```json
{
  "customer_id": {{customer_id}},
  "plate": "BRA1A23",
  "brand": "Honda",
  "model": "Civic",
  "year": 2012
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** vehicle_id.


### 005 | Cadastrar serviço

**Método e URL:** `POST {{base_url}}/api/services`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.

**Body:** raw / JSON.

```json
{
  "name": "Troca de óleo e filtro",
  "price_cents": 6000
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** service_id.


### 006 | Recusar CPF duplicado

**Método e URL:** `POST {{base_url}}/api/customers`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Repita intencionalmente o CPF já cadastrado. A API deve devolver erro de validação em errors.document. HTTP 422 é o resultado correto deste passo.

**Body:** raw / JSON.

```json
{
  "name": "Marcos Silveira",
  "document": "529.982.247-25",
  "email": "cliente@example.test"
}
```

**Status esperado:** HTTP 422.

**Testes automáticos:** Status 422.

**Variáveis salvas:** nenhuma nova variável.


### 007 | Impedir exclusão de cliente com veículo

**Método e URL:** `DELETE {{base_url}}/api/customers/{{customer_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** none, sem corpo.

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 008 | Editar cliente

**Método e URL:** `PATCH {{base_url}}/api/customers/{{customer_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre a atualização do cadastro. PUT envia os campos obrigatórios; PATCH altera apenas os campos enviados. Os preços históricos da OS são preservados.

**Body:** raw / JSON.

```json
{
  "name": "Marcos Silveira atualizado"
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 009 | Listar serviços

**Método e URL:** `GET {{base_url}}/api/services`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 010 | Limpeza - excluir veículo criado

**Método e URL:** `DELETE {{base_url}}/api/vehicles/{{vehicle_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** vehicle_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Exclua apenas o registro criado no bloco inicial. HTTP 204 significa sucesso sem corpo. A exclusão respeita os vínculos, por isso a ordem dos passos importa.

**Body:** none, sem corpo.

**Status esperado:** HTTP 204.

**Testes automáticos:** Status 204.

**Variáveis salvas:** nenhuma nova variável.


### 011 | Limpeza - excluir serviço criado

**Método e URL:** `DELETE {{base_url}}/api/services/{{service_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** service_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Exclua apenas o registro criado no bloco inicial. HTTP 204 significa sucesso sem corpo. A exclusão respeita os vínculos, por isso a ordem dos passos importa.

**Body:** none, sem corpo.

**Status esperado:** HTTP 204.

**Testes automáticos:** Status 204.

**Variáveis salvas:** nenhuma nova variável.


### 012 | Limpeza - excluir cliente criado

**Método e URL:** `DELETE {{base_url}}/api/customers/{{customer_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Exclua apenas o registro criado no bloco inicial. HTTP 204 significa sucesso sem corpo. A exclusão respeita os vínculos, por isso a ordem dos passos importa.

**Body:** none, sem corpo.

**Status esperado:** HTTP 204.

**Testes automáticos:** Status 204.

**Variáveis salvas:** nenhuma nova variável.


### 013 | Confirmar exclusão do cliente

**Método e URL:** `GET {{base_url}}/api/customers/{{customer_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Consulte o cliente que acabou de ser excluído. HTTP 404 confirma que ele não existe mais e é esperado neste passo.

**Body:** none, sem corpo.

**Status esperado:** HTTP 404.

**Testes automáticos:** Status 404.

**Variáveis salvas:** nenhuma nova variável.


## 02 Materiais e estoque

### 014 | parts - cadastrar

**Método e URL:** `POST {{base_url}}/api/parts`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O script salva o ID para as próximas chamadas. Materiais novos começam com saldo zero.

**Body:** raw / JSON.

```json
{
  "name": "Filtro",
  "unit": "unit",
  "price_cents": 2500
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Saldo inicial zero.

**Variáveis salvas:** parts_id.


### 015 | parts - consultar

**Método e URL:** `GET {{base_url}}/api/parts/{{parts_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 016 | parts - listar

**Método e URL:** `GET {{base_url}}/api/parts?per_page=20`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 017 | parts - atualizar

**Método e URL:** `PUT {{base_url}}/api/parts/{{parts_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre a atualização do cadastro. PUT envia os campos obrigatórios; PATCH altera apenas os campos enviados. Os preços históricos da OS são preservados.

**Body:** raw / JSON.

```json
{
  "name": "Filtro atualizado",
  "unit": "unit",
  "price_cents": 2500
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 018 | parts - entrada

**Método e URL:** `POST {{base_url}}/api/parts/{{parts_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre uma entrada IN com quantidade positiva e motivo. O saldo e o histórico devem aumentar juntos.

**Body:** raw / JSON.

```json
{
  "direction": "IN",
  "quantity": "2",
  "reason": "Compra de demonstração"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** nenhuma nova variável.


### 019 | parts - saída

**Método e URL:** `POST {{base_url}}/api/parts/{{parts_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre uma saída OUT de uma unidade, preservando motivo e autoria. O saldo deve diminuir sem ficar negativo.

**Body:** raw / JSON.

```json
{
  "direction": "OUT",
  "quantity": "1",
  "reason": "Ajuste de demonstração"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** nenhuma nova variável.


### 020 | parts - recusar saldo insuficiente

**Método e URL:** `POST {{base_url}}/api/parts/{{parts_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** raw / JSON.

```json
{
  "direction": "OUT",
  "quantity": "999999999",
  "reason": "Validação de saldo"
}
```

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 021 | parts - histórico

**Método e URL:** `GET {{base_url}}/api/parts/{{parts_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre entrada e saída na listagem paginada. Neste ponto da sequência, meta.total deve ser 2.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Dois movimentos.

**Variáveis salvas:** nenhuma nova variável.


### 022 | parts - preservar histórico

**Método e URL:** `DELETE {{base_url}}/api/parts/{{parts_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** none, sem corpo.

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 023 | parts - inativar

**Método e URL:** `PATCH {{base_url}}/api/parts/{{parts_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Desative o material sem excluir seu histórico. Confira active = false. O material será reativado no bloco da OS quando necessário.

**Body:** raw / JSON.

```json
{
  "active": false
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Inativo.

**Variáveis salvas:** nenhuma nova variável.


### 024 | supplies - cadastrar

**Método e URL:** `POST {{base_url}}/api/supplies`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O script salva o ID para as próximas chamadas. Materiais novos começam com saldo zero.

**Body:** raw / JSON.

```json
{
  "name": "Óleo",
  "unit": "L",
  "price_cents": 2500
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Saldo inicial zero.

**Variáveis salvas:** supplies_id.


### 025 | supplies - consultar

**Método e URL:** `GET {{base_url}}/api/supplies/{{supplies_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 026 | supplies - listar

**Método e URL:** `GET {{base_url}}/api/supplies?per_page=20`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 027 | supplies - atualizar

**Método e URL:** `PUT {{base_url}}/api/supplies/{{supplies_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre a atualização do cadastro. PUT envia os campos obrigatórios; PATCH altera apenas os campos enviados. Os preços históricos da OS são preservados.

**Body:** raw / JSON.

```json
{
  "name": "Óleo atualizado",
  "unit": "L",
  "price_cents": 2500
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 028 | supplies - entrada

**Método e URL:** `POST {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre uma entrada IN com quantidade positiva e motivo. O saldo e o histórico devem aumentar juntos.

**Body:** raw / JSON.

```json
{
  "direction": "IN",
  "quantity": "2.500",
  "reason": "Compra de demonstração"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** nenhuma nova variável.


### 029 | supplies - saída

**Método e URL:** `POST {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre uma saída OUT de uma unidade, preservando motivo e autoria. O saldo deve diminuir sem ficar negativo.

**Body:** raw / JSON.

```json
{
  "direction": "OUT",
  "quantity": "1",
  "reason": "Ajuste de demonstração"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** nenhuma nova variável.


### 030 | supplies - recusar saldo insuficiente

**Método e URL:** `POST {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** raw / JSON.

```json
{
  "direction": "OUT",
  "quantity": "999999999",
  "reason": "Validação de saldo"
}
```

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 031 | supplies - histórico

**Método e URL:** `GET {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre entrada e saída na listagem paginada. Neste ponto da sequência, meta.total deve ser 2.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Dois movimentos.

**Variáveis salvas:** nenhuma nova variável.


### 032 | supplies - preservar histórico

**Método e URL:** `DELETE {{base_url}}/api/supplies/{{supplies_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** none, sem corpo.

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 033 | supplies - inativar

**Método e URL:** `PATCH {{base_url}}/api/supplies/{{supplies_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Desative o material sem excluir seu histórico. Confira active = false. O material será reativado no bloco da OS quando necessário.

**Body:** raw / JSON.

```json
{
  "active": false
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Inativo.

**Variáveis salvas:** nenhuma nova variável.


## 03 Abertura e diagnóstico de OS

### 034 | Cadastrar cliente da OS

**Método e URL:** `POST {{base_url}}/api/customers`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.

**Body:** raw / JSON.

```json
{
  "name": "Empresa da demonstração OS",
  "document": "11222333000181"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** order_customer_id.


### 035 | Cadastrar veículo da OS

**Método e URL:** `POST {{base_url}}/api/vehicles`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_customer_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.

**Body:** raw / JSON.

```json
{
  "customer_id": "{{order_customer_id}}",
  "plate": "ZZZ9Z99",
  "brand": "Fiat",
  "model": "Uno",
  "year": 2014
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** order_vehicle_id.


### 036 | Cadastrar serviço da OS

**Método e URL:** `POST {{base_url}}/api/services`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.

**Body:** raw / JSON.

```json
{
  "name": "Diagnóstico e troca",
  "price_cents": 5000
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** order_service_id.


### 037 | Reativar insumo para OS

**Método e URL:** `PATCH {{base_url}}/api/supplies/{{supplies_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** raw / JSON.

```json
{
  "active": true
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 038 | Reativar peça para OS

**Método e URL:** `PATCH {{base_url}}/api/parts/{{parts_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** parts_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** A peça foi inativada na demonstração do catálogo. Reative-a antes de incluir na OS; ela possui uma unidade em estoque.

**Body:** raw / JSON.

```json
{
  "active": true
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 039 | Abrir OS

**Método e URL:** `POST {{base_url}}/api/service-orders`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_service_id, parts_id, supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Crie a OS com serviço de R$ 50, peça de R$ 25 e 0,5 L de insumo a R$ 25/L. Confira Recebida, três itens e items_total_cents = 8750. Ainda não há baixa de estoque.

**Body:** raw / JSON.

```json
{
  "customer_document": "11.222.333/0001-81",
  "vehicle_plate": "ZZZ9Z99",
  "complaint": "Troca de óleo solicitada",
  "items": [
    {
      "kind": "SERVICE",
      "catalog_id": "{{order_service_id}}",
      "quantity": "1"
    },
    {
      "kind": "PART",
      "catalog_id": "{{parts_id}}",
      "quantity": "1"
    },
    {
      "kind": "SUPPLY",
      "catalog_id": "{{supplies_id}}",
      "quantity": "0.5"
    }
  ]
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Recebida; Total exato.

**Variáveis salvas:** order_id.


### 040 | Iniciar diagnóstico

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/diagnosis`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre o diagnóstico da equipe e confira a transição Recebida para Em diagnóstico. O texto deve estar no corpo JSON.

**Body:** raw / JSON.

```json
{
  "diagnosis": "Confirmada necessidade de troca"
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Em diagnóstico.

**Variáveis salvas:** nenhuma nova variável.


### 041 | Consultar OS

**Método e URL:** `GET {{base_url}}/api/service-orders/{{order_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 042 | Listar OS

**Método e URL:** `GET {{base_url}}/api/service-orders`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


## 04 Orçamentos versionados

### 043 | Apresentar versão 1

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/budgets`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Gere e disponibilize a proposta v1 de 8750 centavos. A OS muda para Aguardando aprovação. O orçamento é PENDING.

**Body:** none, sem corpo.

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Total calculado.

**Variáveis salvas:** budget_id, first_budget_id.


### 044 | Consultar orçamento

**Método e URL:** `GET {{base_url}}/api/service-orders/{{order_id}}/budgets/{{budget_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** budget_id, order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 045 | Listar versões

**Método e URL:** `GET {{base_url}}/api/service-orders/{{order_id}}/budgets`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Confira o número da versão e a decisão da proposta. Revisões criam novos orçamentos; a proposta anterior continua consultável e não é sobrescrita.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 046 | Recusar duplicação

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/budgets`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** none, sem corpo.

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 047 | Iniciar revisão

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/revise-budget`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 048 | Apresentar versão 2

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/budgets`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Confira o número da versão e a decisão da proposta. Revisões criam novos orçamentos; a proposta anterior continua consultável e não é sobrescrita.

**Body:** none, sem corpo.

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Nova versão pendente.

**Variáveis salvas:** budget_id.


### 049 | Preservar versão anterior

**Método e URL:** `GET {{base_url}}/api/service-orders/{{order_id}}/budgets/{{first_budget_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** first_budget_id, order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Confira o número da versão e a decisão da proposta. Revisões criam novos orçamentos; a proposta anterior continua consultável e não é sobrescrita.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Versão original.

**Variáveis salvas:** nenhuma nova variável.


### 050 | Consultar OS aguardando aprovação

**Método e URL:** `GET {{base_url}}/api/service-orders/{{order_id}}`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Aguardando aprovação.

**Variáveis salvas:** nenhuma nova variável.


## 05 Acesso e decisão do cliente

### 051 | Emitir credencial da OS

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/customer-access`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Como administrador, emita a credencial específica da OS. customer_token é salvo automaticamente. A resposta deve conter Cache-Control com no-store; não mostre o token na gravação.

**Body:** none, sem corpo.

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Não armazenar em cache.

**Variáveis salvas:** customer_token.


### 052 | Cliente consulta orçamento

**Método e URL:** `GET {{base_url}}/api/customer/service-orders/{{order_id}}/budgets/{{budget_id}}`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** budget_id, customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Consulte a visão do cliente com customer_token, restrito à própria OS. Observe status, proposta e histórico sem confundir essa credencial com o JWT administrativo.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 053 | Cliente rejeita proposta

**Método e URL:** `POST {{base_url}}/api/customer/service-orders/{{order_id}}/budgets/{{budget_id}}/decision`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** budget_id, customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Com o token do cliente, rejeite a versão 2. A rejeição impede execução e preserva a proposta. A OS continua Aguardando aprovação.

**Body:** raw / JSON.

```json
{
  "decision": "REJECTED"
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 054 | Rejeição mantém etapa

**Método e URL:** `GET {{base_url}}/api/customer/service-orders/{{order_id}}`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Consulte a visão do cliente com customer_token, restrito à própria OS. Observe status, proposta e histórico sem confundir essa credencial com o JWT administrativo.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Aguardando aprovação.

**Variáveis salvas:** nenhuma nova variável.


### 055 | Equipe inicia revisão

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/revise-budget`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200.

**Variáveis salvas:** nenhuma nova variável.


### 056 | Apresentar versão 3

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/budgets`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Confira o número da versão e a decisão da proposta. Revisões criam novos orçamentos; a proposta anterior continua consultável e não é sobrescrita.

**Body:** none, sem corpo.

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201; Versão 3 pendente.

**Variáveis salvas:** budget_id.


### 057 | Recusar decisão de versão antiga

**Método e URL:** `POST {{base_url}}/api/customer/service-orders/{{order_id}}/budgets/{{first_budget_id}}/decision`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** customer_token, first_budget_id, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** raw / JSON.

```json
{
  "decision": "APPROVED"
}
```

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 058 | Cliente aprova proposta vigente

**Método e URL:** `POST {{base_url}}/api/customer/service-orders/{{order_id}}/budgets/{{budget_id}}/decision`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** budget_id, customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Aprove a versão 3 usando a credencial do cliente. Confira decision = APPROVED. A decisão sozinha não inicia a execução.

**Body:** raw / JSON.

```json
{
  "decision": "APPROVED"
}
```

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Aprovado.

**Variáveis salvas:** nenhuma nova variável.


### 059 | Recusar decisão repetida

**Método e URL:** `POST {{base_url}}/api/customer/service-orders/{{order_id}}/budgets/{{budget_id}}/decision`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** budget_id, customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** raw / JSON.

```json
{
  "decision": "APPROVED"
}
```

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 060 | Cliente acompanha antes da execução

**Método e URL:** `GET {{base_url}}/api/customer/service-orders/{{order_id}}`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Consulte a visão do cliente com customer_token, restrito à própria OS. Observe status, proposta e histórico sem confundir essa credencial com o JWT administrativo.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Sem dados internos.

**Variáveis salvas:** nenhuma nova variável.


## 06 Execução e entrega

### 061 | Zerar saldo da demonstração

**Método e URL:** `POST {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Saem os 1,5 L que restaram no insumo após entrada de 2,5 L e saída anterior de 1 L. O saldo fica zero para provocar a recusa de início.

**Body:** raw / JSON.

```json
{
  "direction": "OUT",
  "quantity": "1.5",
  "reason": "Demonstrar falta de estoque"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** nenhuma nova variável.


### 062 | Recusar início sem estoque

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/start-execution`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** A ordem está aprovada, mas o insumo está sem saldo. Espere 409. A transação deve preservar o status e também a peça, sem consumo parcial.

**Body:** none, sem corpo.

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 063 | Repor estoque

**Método e URL:** `POST {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre entrada de 1 L. Agora há saldo para consumir 0,5 L, além da peça disponível.

**Body:** raw / JSON.

```json
{
  "direction": "IN",
  "quantity": "1",
  "reason": "Compra para a OS"
}
```

**Status esperado:** HTTP 201.

**Testes automáticos:** Status 201.

**Variáveis salvas:** nenhuma nova variável.


### 064 | Iniciar execução aprovada

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/start-execution`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Inicie a ordem aprovada. Confira Em execução; a peça passa de 1 para 0 unidade, e o insumo passa de 1 para 0,5 L. Baixa e status são atômicos.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Em execução.

**Variáveis salvas:** nenhuma nova variável.


### 065 | Recusar consumo repetido

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/start-execution`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.

**Body:** none, sem corpo.

**Status esperado:** HTTP 409.

**Testes automáticos:** Status 409.

**Variáveis salvas:** nenhuma nova variável.


### 066 | Finalizar serviços

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/finish`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Finalize uma OS Em execução. Confira Finalizada e o instante finished_at, que encerra a medição do tempo de execução.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Finalizada.

**Variáveis salvas:** nenhuma nova variável.


### 067 | Entregar veículo

**Método e URL:** `POST {{base_url}}/api/service-orders/{{order_id}}/deliver`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** order_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Registre a retirada da OS Finalizada. Confira Entregue e o instante delivered_at. A espera de retirada não altera o tempo de serviço.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Entregue.

**Variáveis salvas:** nenhuma nova variável.


### 068 | Cliente acompanha entrega

**Método e URL:** `GET {{base_url}}/api/customer/service-orders/{{order_id}}`

**Autenticação:** Bearer Token {{customer_token}}.

**Antes de enviar:** customer_token, order_id devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Consulte a visão do cliente com customer_token, restrito à própria OS. Observe status, proposta e histórico sem confundir essa credencial com o JWT administrativo.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Entrega visível.

**Variáveis salvas:** nenhuma nova variável.


### 069 | Conferir baixa ligada à OS

**Método e URL:** `GET {{base_url}}/api/supplies/{{supplies_id}}/stock-movements`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** supplies_id, access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Confira a única saída do insumo associada à OS, quantidade 0.500. O teste diferencia o consumo da OS dos ajustes manuais anteriores.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Status 200; Baixa única.

**Variáveis salvas:** nenhuma nova variável.


## 07 Indicadores

### 070 | Tempo médio de execução

**Método e URL:** `GET {{base_url}}/api/reports/service-execution-time`

**Autenticação:** Herdar autenticação da coleção: Bearer {{access_token}}.

**Antes de enviar:** access_token devem estar preenchidos pelos passos anteriores ou por você no ambiente local.

**O que fazer e explicar:** Confira orders_count maior que zero e average_seconds não negativo. O tempo pode ser zero numa execução rápida. Não inclui espera pela retirada.

**Body:** none, sem corpo.

**Status esperado:** HTTP 200.

**Testes automáticos:** Indicador disponível ao administrador.

**Variáveis salvas:** nenhuma nova variável.


# Todas as rotas para consulta manual

São 53 operações documentadas mais GET /up e GET /api/openapi. PUT e PATCH contam separadamente. Os corpos e as credenciais estão preparados na coleção de referência; IDs devem ser configurados nela.

| Método | Rota | Respostas documentadas |
|---|---|---|
| GET | /up | 200 |
| GET | /api/openapi | 200 |
| POST | /api/auth/token | 200, 401, 422, 429 |
| GET | /api/customers | 200, 401, 403, 422 |
| POST | /api/customers | 201, 401, 403, 409, 422 |
| GET | /api/customers/{id} | 200, 401, 403, 404, 409, 422 |
| PUT | /api/customers/{id} | 200, 401, 403, 404, 409, 422 |
| PATCH | /api/customers/{id} | 200, 401, 403, 404, 409, 422 |
| DELETE | /api/customers/{id} | 204, 401, 403, 404, 409, 422 |
| GET | /api/vehicles | 200, 401, 403, 422 |
| POST | /api/vehicles | 201, 401, 403, 409, 422 |
| GET | /api/vehicles/{id} | 200, 401, 403, 404, 409, 422 |
| PUT | /api/vehicles/{id} | 200, 401, 403, 404, 409, 422 |
| PATCH | /api/vehicles/{id} | 200, 401, 403, 404, 409, 422 |
| DELETE | /api/vehicles/{id} | 204, 401, 403, 404, 409, 422 |
| GET | /api/services | 200, 401, 403, 422 |
| POST | /api/services | 201, 401, 403, 409, 422 |
| GET | /api/services/{id} | 200, 401, 403, 404, 409, 422 |
| PUT | /api/services/{id} | 200, 401, 403, 404, 409, 422 |
| PATCH | /api/services/{id} | 200, 401, 403, 404, 409, 422 |
| DELETE | /api/services/{id} | 204, 401, 403, 404, 409, 422 |
| GET | /api/parts | 200, 401, 403, 422 |
| POST | /api/parts | 201, 401, 403, 409, 422 |
| GET | /api/parts/{id} | 200, 401, 403, 404, 409, 422 |
| PUT | /api/parts/{id} | 200, 401, 403, 404, 409, 422 |
| PATCH | /api/parts/{id} | 200, 401, 403, 404, 409, 422 |
| DELETE | /api/parts/{id} | 204, 401, 403, 404, 409, 422 |
| GET | /api/parts/{id}/stock-movements | 200, 401, 403, 404, 409, 422 |
| POST | /api/parts/{id}/stock-movements | 201, 401, 403, 404, 409, 422 |
| GET | /api/supplies | 200, 401, 403, 422 |
| POST | /api/supplies | 201, 401, 403, 409, 422 |
| GET | /api/supplies/{id} | 200, 401, 403, 404, 409, 422 |
| PUT | /api/supplies/{id} | 200, 401, 403, 404, 409, 422 |
| PATCH | /api/supplies/{id} | 200, 401, 403, 404, 409, 422 |
| DELETE | /api/supplies/{id} | 204, 401, 403, 404, 409, 422 |
| GET | /api/supplies/{id}/stock-movements | 200, 401, 403, 404, 409, 422 |
| POST | /api/supplies/{id}/stock-movements | 201, 401, 403, 404, 409, 422 |
| GET | /api/service-orders | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders | 201, 401, 403, 404, 409, 422 |
| GET | /api/service-orders/{id} | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders/{id}/diagnosis | 200, 401, 403, 404, 409, 422 |
| PUT | /api/service-orders/{id}/items | 200, 401, 403, 404, 409, 422 |
| GET | /api/service-orders/{id}/budgets | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders/{id}/budgets | 201, 401, 403, 404, 409, 422 |
| GET | /api/service-orders/{id}/budgets/{budget} | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders/{id}/revise-budget | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders/{id}/customer-access | 201, 401, 403, 404, 409, 422, 429 |
| DELETE | /api/service-orders/{id}/customer-access | 204, 401, 403, 404, 409, 422 |
| GET | /api/customer/service-orders/{id} | 200, 401, 404, 409, 422, 429 |
| GET | /api/customer/service-orders/{id}/budgets/{budget} | 200, 401, 404, 409, 422, 429 |
| POST | /api/customer/service-orders/{id}/budgets/{budget}/decision | 200, 401, 404, 409, 422, 429 |
| POST | /api/service-orders/{id}/start-execution | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders/{id}/finish | 200, 401, 403, 404, 409, 422 |
| POST | /api/service-orders/{id}/deliver | 200, 401, 403, 404, 409, 422 |
| GET | /api/reports/service-execution-time | 200, 401, 403 |

## Substituir todos os itens da OS

Envie PUT /api/service-orders/{{order_id}}/items apenas em Recebida ou Em diagnóstico. O corpo contém a lista completa, não somente o item novo:

```json
{
  "items": [
    {
      "kind": "SERVICE",
      "catalog_id": "{{service_id}}",
      "quantity": "1"
    },
    {
      "kind": "PART",
      "catalog_id": "{{parts_id}}",
      "quantity": "1"
    },
    {
      "kind": "SUPPLY",
      "catalog_id": "{{supplies_id}}",
      "quantity": "0.5"
    }
  ]
}
```

Essa operação não faz parte dos 70 passos do Runner. Se usada depois do fluxo principal, a OS já entregue recusará a alteração com 409. Para exemplificar corretamente, use uma OS nova em diagnóstico ou revise uma proposta ainda aguardando aprovação.

## Revogar e reemitir o acesso do cliente

DELETE /api/service-orders/{{order_id}}/customer-access usa JWT administrativo e não tem corpo; o sucesso é 204. O token anterior deixa de autorizar consultas. POST no mesmo caminho emite uma nova credencial e substitui a anterior. Faça isso em um cenário adicional, para não invalidar o token durante a demonstração principal.
