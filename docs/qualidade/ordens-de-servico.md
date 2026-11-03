# Validação de abertura e diagnóstico de OS

Execução local em 03/11/2026, PHP 8.5.8 e SQLite, sem Docker.

- Suíte completa: 113 testes aprovados, 1 ignorado por ser específico de Windows, 475 asserções.
- Cobertura de linhas: 492/501, ou 98,20%, com PCOV 1.0.12.
- Verificações: CPF/CNPJ e placa normalizados; vínculo cliente/veículo; preços históricos; arredondamento por item e soma exata; limites monetários; tipos/quantidades; materiais inativos; rollback na criação e substituição; proteção das referências; histórico de diagnóstico; matriz de transições e autenticação.
- OpenAPI validada com Swagger Parser. Pint, Composer validate e diff-check passaram.
- Coleção Postman executada com Newman contra a aplicação HTTP e banco temporário novo: 41 requisições, 51 asserções, sem falhas.

Os status finais já são definidos e testados como regra de domínio. Esta etapa
não implementa orçamento, decisão do cliente, execução ou entrega. A cobertura
mede apenas o código existente. Concorrência no MySQL continua pendente.

Para repetir: `composer test:coverage`; para a coleção, use banco exclusivo
novo, crie administrador e informe as variáveis locais de autenticação.
A demonstração preserva a OS e seus cadastros para consulta.
