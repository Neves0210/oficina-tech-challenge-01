# Validação do acesso do cliente

Execução local em 03/11/2026, PHP 8.5.8 e SQLite, sem Docker.

- 126 testes aprovados, 1 ignorado por ser específico de Windows, 653 asserções.
- Cobertura de linhas de app/: 609/618 (98,54%).
- Coleção Postman via Newman no backend HTTP temporário: 59 requisições, 78 asserções, sem falhas.
- OpenAPI validada com Swagger Parser. Pint, Composer e diff-check passaram.

Verificados: segredo armazenado apenas como hash; escopo por OS; omissão de
dados internos; rejeição de token ausente, inválido, expirado, revogado e de
outra OS; rotação; separação do JWT administrativo; decisão somente da versão
vigente; preservação da primeira decisão e da versão rejeitada; aprovação de
nova versão; revalidação da credencial dentro da transação.

São testes locais com dados fictícios, não um pentest. A decisão comprova posse
da credencial entregue pelo atendimento, sem alegar assinatura digital.
