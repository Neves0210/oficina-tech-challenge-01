# Arquitetura e decisão de banco de dados

## Limite da solução

O projeto será um **back-end monolítico em Laravel**, com uma única aplicação e um banco relacional. As funcionalidades serão expostas por APIs RESTful. Não há interface visual no escopo da Fase 1. A organização em camadas mantém as regras de negócio independentes de controllers, formato HTTP e detalhes do banco.

| Camada | Responsabilidade | Exemplo futuro |
| --- | --- | --- |
| Interface | Receber requisições, validar formato, autenticar, autorizar e devolver respostas HTTP | Rotas, controllers, requests e resources da API |
| Aplicação | Orquestrar casos de uso e transações, consultar repositórios e publicar o resultado | Abrir OS, apresentar orçamento, iniciar execução |
| Domínio | Expressar invariantes, cálculos e transições sem depender do Laravel | OS, orçamento, dinheiro, documento, placa, políticas de status |
| Infraestrutura | Persistência, JWT, documentação OpenAPI e integrações de entrega | Eloquent, migrations, repositórios, armazenamento |

Os diretórios concretos serão criados conforme cada caso de uso for implementado. Esta separação é uma regra de dependência: a camada de domínio não conhece controllers nem tabelas; a aplicação usa contratos para acessar a infraestrutura.

## Contextos e agregados

| Contexto proposto | Raízes de agregados | Invariantes sob sua responsabilidade |
| --- | --- | --- |
| Atendimento | Cliente e Veículo | Documento válido e único; placa válida e única; vínculo do veículo ao cliente. |
| Catálogo | Serviço e Material | Definição de preço, tipo e unidade; material distingue peça de insumo. |
| Ordens de Serviço | OS | Status, itens, diagnóstico, versões de orçamento e decisão do cliente. |
| Estoque | Saldo de Material | Saldo não negativo e movimentação rastreável; o saldo referencia o material do catálogo. |

Esses são limites lógicos **dentro do mesmo monólito**, não serviços distribuídos. Uma OS referencia cadastros de outros contextos por identificador; copia os valores relevantes para seu histórico. A execução coordena OS e estoque numa transação do banco.

**Objetos de valor previstos:** CPF/CNPJ, Placa, Dinheiro, Quantidade e Status da OS. **Eventos de domínio relevantes:** OS Recebida, Diagnóstico Iniciado, Orçamento Apresentado, Orçamento Aprovado ou Rejeitado, Execução Iniciada, OS Finalizada e Veículo Entregue. Eles descrevem fatos do domínio; não implicam fila ou mensageria nesta fase.

## Banco escolhido: MySQL

Escolhemos **MySQL** para a implementação. O problema tem relações claras entre clientes, veículos, OS, itens e materiais. Chaves estrangeiras e restrições de unicidade ajudam a preservar essas relações; transações permitem registrar, de forma indivisível, a baixa de materiais e o início da execução. Índices em documento, placa, status e datas sustentam consultas administrativas e o acompanhamento. O banco roda no ambiente Docker previsto pelo enunciado, o que facilita reproduzir a execução local em Windows e macOS.

A versão e a imagem do servidor serão fixadas na etapa de ambiente Docker, junto com as instruções de execução. Valores monetários serão guardados como centavos inteiros, e quantidades fracionárias de insumos usarão decimal exato. O banco não substitui as validações de CPF/CNPJ, placa ou transições: essas regras permanecem no domínio e são protegidas por testes.

## Rastreabilidade da Fase 1

- [Linguagem ubíqua](linguagem-ubiqua.md): termos comuns entre documentação, API e código.
- [Regras da OS](regras-da-os.md): orçamento, decisão, estoque, status e tempo médio.
- [Modelo de dados](modelo-de-dados.md): entidades, relacionamentos e restrições planejadas.

Nas próximas etapas, a documentação será ajustada para corresponder ao comportamento implementado. Event Storming dos três fluxos, diagramas pedidos na disciplina e evidências da solução final compõem a documentação DDD exigida para a entrega.
