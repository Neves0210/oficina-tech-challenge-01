# Modelo de dados implementado

O esquema está em `database/migrations`. Os nomes abaixo são físicos.
Valores monetários usam centavos inteiros; quantidades usam decimal(12,3).
O [DER completo](catalogo-de-diagramas.md#d07---der--atendimento-orçamento-e-estoque-implementados) complementa a descrição.

| Tabela | Papel e integridade |
|---|---|
| customers | Documento normalizado e único; CPF/CNPJ validado pelo domínio. |
| vehicles | Placa única, FK customer_id restrita na exclusão. |
| workshop_services | Serviço com preço em centavos; referências históricas protegidas. |
| materials | PART ou SUPPLY; unidade, preço, saldo decimal e active. |
| service_orders | FKs customer_id e vehicle_id; seis status; complaint, diagnosis, started_at, finished_at e delivered_at. |
| service_order_items | FK da OS; referência a serviço OU material, nome, unidade, quantidade, preço e subtotal copiados. |
| order_budgets | FK da OS; versão única por OS, total, decisão PENDING/APPROVED/REJECTED e instantes de apresentação/decisão. |
| order_budget_items | Fotografia imutável dos itens da proposta. |
| order_status_history | Status anterior/novo, data e administrador quando aplicável. |
| stock_movements | Material, administrador, direção IN/OUT, quantidade positiva, motivo e OS opcional; unicidade OS/material/direção. |
| order_customer_access | Uma credencial por OS; hash SHA-256 único, validade e administrador emissor; sem token em texto puro. |
| users | Conta administrativa com is_admin e senha em hash; independente de customers. |

As FKs restringem exclusão de cadastros usados. Relações internas de OS/orçamento
usam cascata; a API não oferece exclusão de OS. Inativar materiais preserva o
histórico. Peças e serviços exigem quantidade inteira; insumos aceitam três casas.

A escolha de serviço OU material é validada no repositório ao construir o item,
assim como status, decisão vigente, propriedade do veículo e saldo não negativo.
Não se atribui ao banco uma restrição CHECK que não existe nas migrations.
Orçamento, movimentações e mudanças de estado usam transação.

O início bloqueia a OS e os materiais em ordem crescente, soma linhas repetidas
e confere todos os saldos antes de gravar. O bloqueio real sob concorrência
MySQL ainda precisa de validação no ambiente Windows.
