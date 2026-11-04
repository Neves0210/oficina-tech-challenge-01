# Postman da oficina

Comece pelo [guia completo](guia-postman-completo.md), que contém preparo, importação, variáveis, roteiro de 14m30s e os 70 passos com método, URL, corpo JSON, autenticação e resultado esperado.

Importe:

1. [Fluxo guiado de 70 chamadas](oficina-fluxo-guiado.postman_collection.json).
2. [Ambiente Docker para preencher localmente](oficina-docker.postman_environment.json).
3. Opcionalmente, [catálogo manual de todas as rotas](oficina-referencia-completa.postman_collection.json): 53 operações da OpenAPI, GET /up e GET /api/openapi. Não execute esse catálogo inteiro no Runner.

Preencha apenas base_url, admin_email e admin_password no ambiente. Os IDs e tokens são salvos na coleção guiada; não crie variáveis vazias com os mesmos nomes no ambiente.

O fluxo guiado foi [validado](validacao-fluxo-guiado.json) com 70 chamadas e 94 asserções, sem falhas, usando banco MySQL novo. Ele inclui serviço, peça e insumo na OS, com total de R$ 87,50. A [coleção original de 69 chamadas](oficina-cadastros.postman_collection.json), usada nas evidências anteriores, permanece disponível.

Para gerar PDF e ZIP públicos novamente, execute `python scripts/build-postman-guide.py`, com reportlab instalado. Os arquivos são criados em `output/pdf/guia-postman-completo.pdf` e `output/postman/oficina-postman-completo.zip`. O gerador não lê credenciais locais.
