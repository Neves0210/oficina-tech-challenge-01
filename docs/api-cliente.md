# Acompanhamento e decisão do cliente

O MVP usa uma credencial aleatória limitada a uma OS. Não há cadastro público
de administrador nem autorização baseada apenas em CPF/CNPJ ou placa.

## Emissão administrativa

Com JWT administrativo, `POST /api/service-orders/{id}/customer-access` retorna
201 com `access_token`, `token_type: Bearer`, `expires_in: 86400` e `expires_at`.
O segredo tem 32 bytes aleatórios, é mostrado somente nessa resposta e o banco
guarda apenas seu SHA-256. Reemitir substitui a credencial e invalida a anterior.
`DELETE` no mesmo caminho revoga o acesso; repetir a revogação retorna 204.

A equipe entrega a credencial ao responsável por canal confiável. Não foi
implementado envio automático por e-mail/WhatsApp. Em ambiente exposto, usar
HTTPS. Não colocar a credencial em URL, logs ou arquivos versionados. A resposta
de emissão e as consultas do cliente usam Cache-Control no-store.

## Uso pelo cliente

Enviar `Authorization: Bearer <access_token>` nas rotas:

- `GET /api/customer/service-orders/{id}`: andamento, diagnóstico, itens, histórico e propostas da própria OS.
- `GET /api/customer/service-orders/{id}/budgets/{budget}`: versão do orçamento da própria OS.
- `POST /api/customer/service-orders/{id}/budgets/{budget}/decision`, corpo `{"decision":"APPROVED"}` ou `{"decision":"REJECTED"}`.

Somente a proposta vigente, PENDING, em OS Aguardando aprovação pode receber
decisão. O sistema grava a decisão e o instante. A OS permanece Aguardando
aprovação: aprovar não inicia execução, rejeitar não cria outro status.
Repetir uma decisão retorna 409 e preserva a primeira; consulte o orçamento se
o resultado de uma chamada ficou incerto. Revisar a proposta exige nova versão
e nova decisão. O histórico anterior não é reescrito.

Token expirado, revogado, inválido ou de outra OS retorna o mesmo 401. O JWT
administrativo não substitui a credencial do cliente nessas rotas; a credencial
da OS não concede acesso administrativo. As respostas do cliente omitem IDs de
usuários internos e detalhes dos cadastros. Há limitação de requisições por IP.
A autorização é revalidada sob bloqueio da OS antes de persistir uma decisão.

Esse modelo autentica a posse da credencial entregue pelo atendimento; não
constitui verificação de identidade civil nem assinatura digital do cliente.
