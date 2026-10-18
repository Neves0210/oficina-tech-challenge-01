# API de cadastros

As APIs administrativas de clientes, veículos e serviços estão implementadas. Elas usam JWT de uma conta administrativa. A especificação [OpenAPI](openapi.json) pode ser importada no Postman, Insomnia ou Swagger Editor e também é servida em `GET /api/openapi`.

Para um roteiro guiado, importe a [coleção do Postman](postman/oficina-cadastros.postman_collection.json). Crie um ambiente local no Postman com `admin_email` e `admin_password` da conta criada no terminal. A coleção usa `http://127.0.0.1:8000`, captura token e identificadores automaticamente e possui verificações de resposta. Execute na ordem: autenticar, cadastrar, consultar, testar duplicidade e vínculo, editar e excluir os registros fictícios criados. Rode em um banco de demonstração exclusivo; se interromper antes da limpeza, os cadastros permanecerão para consulta.

## Acesso

Crie uma conta com `php artisan app:create-admin`. O comando solicita nome, e-mail e senha de forma interativa; a senha não é exibida nem passada em argumento de terminal. Não existe registro público de administrador.

Envie e-mail e senha em JSON para `POST /api/auth/token`. Use o `access_token` recebido no cabeçalho `Authorization: Bearer <token>`. O token expira em uma hora. Uma conta removida ou que perdeu a permissão administrativa deixa de conseguir acessar os cadastros, mesmo com um token ainda vigente. A emissão permite até dez tentativas por minuto e IP.

## Recursos

| Recurso | Coleção | Campos de entrada |
| --- | --- | --- |
| Cliente | `/api/customers` | `name`, `document`, `email` opcional, `phone` opcional |
| Veículo | `/api/vehicles` | `customer_id`, `plate`, `brand`, `model`, `year` |
| Serviço | `/api/services` | `name`, `description` opcional, `price_cents` |

Cada coleção aceita GET e POST. O caminho com identificador aceita GET, PUT, PATCH e DELETE. PUT exige novamente os campos obrigatórios; PATCH aceita alterações parciais. Campos não reconhecidos não são persistidos.

As listagens usam `page` e `per_page` (padrão 20, máximo 100) e retornam `data` e `meta`. Criação retorna 201; leitura/edição retornam 200 com `data`; exclusão retorna 204. Falhas retornam JSON: 401 para autenticação, 403 para permissão, 404 para registro ausente, 409 para conflito de integridade e 422 para validação.

## Regras verificadas

- CPF e CNPJ têm formato e dígitos verificadores validados. Documentos são únicos e armazenados sem máscara; CNPJ é normalizado em maiúsculas. A validação suporta também o formato alfanumérico, seguindo o [manual técnico da Receita Federal](https://www.gov.br/receitafederal/pt-br/centrais-de-conteudo/publicacoes/documentos-tecnicos/cnpj/manual-dv-cnpj.pdf).
- Placas brasileiras antigas e Mercosul são normalizadas e únicas.
- Veículo exige cliente existente e ano entre 1886 e o ano corrente mais um. Essa faixa é uma política do cadastro.
- Cliente com veículo vinculado não pode ser excluído; remova o vínculo antes.
- Preço de serviço é inteiro em centavos, de zero a 2.147.483.647. Por exemplo, R$ 60,00 corresponde a `6000`.

## Organização implementada

`app/Http` recebe e valida requisições; `app/Application/Registration` coordena os cadastros; `app/Domain` contém os contratos de persistência e os objetos de valor de documento e placa; `app/Infrastructure` implementa os repositórios Eloquent e o JWT.

Os nomes físicos das tabelas são `customers`, `vehicles` e `workshop_services`, correspondentes a clientes, veículos e serviços do modelo lógico da documentação DDD. A tabela `users` tem `is_admin` para o acesso administrativo.

OS, orçamento, estoque, acompanhamento e seus testes pertencem às etapas seguintes.
