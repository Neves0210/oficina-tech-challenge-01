# Roteiro da apresentação da oficina

Gabriel De Barros Neves | rm378693 | Grupo 19 | Discord Nev3s

Duração planejada: **14 minutos e 30 segundos**, com 30 segundos de margem até o limite de 15 minutos. Base: enunciado `Fase 1 - Tech Challenge.pdf`, páginas 3 a 11, especialmente a lista obrigatória do vídeo nas páginas 6 e 7. A demonstração é da API; o projeto não tem interface gráfica.

## Preparação antes de gravar

1. Inicie o Docker Desktop e execute `powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1`.
2. Reserve um banco exclusivo e vazio para a gravação. Ensaios anteriores deixam históricos e documentos fixos; use outro projeto Compose/volume para um novo ensaio, seguindo o guia Docker.
3. Crie uma conta com `docker compose exec app php artisan app:create-admin`. Configure um ambiente local no Postman com `base_url=http://127.0.0.1:8081` e as credenciais. Alternativa: `scripts/prepare-demo.php`, conforme o guia.
4. Importe a coleção, habilite o mascaramento de segredos e não abra o ambiente nem o valor de tokens durante a gravação. Mostre o resultado da autenticação sem revelar o Bearer.
5. Deixe abertos: README, arquitetura, Event Storming, diagramas, OpenAPI, coleção Postman, terminal e relatórios. Abra os arquivos antes para evitar perder tempo procurando.
6. Execute previamente os testes, a cobertura, a concorrência e a auditoria. Deixe os comandos prontos para executar novamente no vídeo; a compilação da imagem deve ocorrer antes da gravação.
7. Registre `git branch --show-current`, `git rev-parse HEAD` e `git status --short`. Use exatamente esse hash no PDF e não altere o código entre a demonstração e a entrega.
8. Grave tela em resolução legível, fonte do terminal ampliada, áudio testado e cronômetro visível para você. Não precisa ler as falas literalmente: elas orientam o conteúdo.

## 00m00s a 00m40s Identificação e proposta

**Na tela:** primeira página da entrega e README.

**Fala sugerida:** “Olá, sou Gabriel De Barros Neves, RM rm378693, do Grupo 19. Meu usuário no Discord é Nev3s. Este é o Tech Challenge da Fase 1 de Software Architecture. Desenvolvi o backend de uma oficina para organizar clientes, veículos, ordens de serviço, orçamentos e estoque. A proposta é substituir controles dispersos por um fluxo rastreável, com aprovação do cliente, acesso seguro e histórico das etapas.”

**Evidência:** objetivo, identificação e tecnologias. Cite PHP 8.4, Laravel 13, MySQL 8.4 e Docker.

## 00m40s a 02m05s Arquitetura e banco de dados

**Na tela:** diretório `app/`, ADRs 001 e 002 e diagrama D03.

**Ações:** mostre Http, Application, Domain e Infrastructure; abra rapidamente OrderStatus e EloquentExecutionRepository.

**Fala sugerida:** “A solução é um monólito organizado em camadas. Http recebe as requisições e valida a entrada. Application coordena os casos de uso. Domain contém regras, objetos de valor e contratos. Infrastructure implementa persistência e segurança. Os contratos são ligados às implementações no AppServiceProvider. Escolhi MySQL porque o domínio tem relacionamentos fortes e exige integridade entre cliente, veículo, OS, orçamento e movimentações. Transações e bloqueios de linha permitem que a mudança de status e a baixa de materiais aconteçam juntas.”

**Complete:** SQLite é usado na suíte rápida e na medição de cobertura; o fluxo e a concorrência também foram validados no MySQL real. Nginx e PHP-FPM compõem a execução local; isso continua sendo uma aplicação monolítica.

## 02m05s a 03m40s Event Storming e elementos de DDD

**Na tela:** três fluxos de `docs/ddd/event-storming.md`, catálogo de diagramas e linguagem ubíqua.

**Ações:** percorra ES-01, ES-02 e ES-03; destaque ator, comando, evento e política em uma linha de cada fluxo.

**Fala sugerida:** “A modelagem separa criação da OS, acompanhamento e execução, e gestão de peças e insumos. No primeiro fluxo, o atendente abre a ordem e o diagnóstico define os itens do orçamento. No segundo, o cliente aprova a proposta vigente e a equipe inicia, finaliza e entrega o serviço. No terceiro, entradas e saídas preservam o histórico e impedem saldo negativo. Os eventos representam fatos do negócio; não existe mensageria implementada neste MVP. A OS é a raiz que protege seus itens e versões de orçamento. O estoque protege o saldo e suas movimentações. Os contextos são limites lógicos dentro do monólito.”

**Complete:** mostre entidades Cliente, Veículo e OS; objetos de valor DocumentNumber, Plate, Money e Quantity; enums OrderStatus e BudgetDecision. Explique dinheiro em centavos, quantidades exatas e o vocabulário comum. Use a documentação atual do GitHub; o FigJam antigo é histórico.

## 03m40s a 04m30s Aplicação em execução e OpenAPI

**Na tela:** terminal com `docker compose ps`, navegador em `/up` e `/api/openapi`.

**Ações:** mostre os serviços app, web e mysql; abra o JSON OpenAPI ou importe a especificação no Postman para navegar pelas rotas e schemas.

**Fala sugerida:** “O ambiente completo sobe pelo script do README. O Dockerfile inclui o código e as dependências; o Compose orquestra PHP, Nginx e MySQL. O endpoint de saúde confirma a execução e a documentação OpenAPI descreve autenticação, recursos, payloads e respostas. A especificação também está versionada no repositório.”

**Não espere compilar no vídeo:** a imagem já deve estar pronta. Não apresente o JSON como uma interface Swagger UI: é a especificação OpenAPI.

## 04m30s a 06m20s Autenticação e gestão administrativa

**Na tela:** Postman, requisições iniciais e pasta Materiais e estoque.

**Ações:** execute os 13 itens iniciais em ordem. Mostre token administrativo, cadastro e consulta de cliente, veículo e serviço, edição, exclusão permitida e recusa de CPF duplicado. Execute a pasta de materiais em ordem e destaque cadastro de peça e insumo, entrada, saída, saldo, histórico e recusa por estoque insuficiente.

**Fala sugerida:** “As rotas administrativas exigem JWT. A conta é criada por procedimento interativo, e o login retorna um token com validade. CPF/CNPJ e placas são normalizados e validados, com restrições de unicidade. Os CRUDs cobrem clientes, veículos, serviços, peças e insumos. Peças usam quantidades inteiras e insumos aceitam frações exatas. O saldo é alterado por movimentações identificadas, com motivo e autor, preservando o histórico.”

**Controle de tempo:** use o Runner para as chamadas repetidas e abra só respostas representativas. Não narre cada requisição. Não exiba senha ou token.

## 06m20s a 09m20s Fluxo completo da ordem de serviço

**Na tela:** pastas Abertura e diagnóstico de OS, Orçamentos versionados, Acesso e decisão do cliente e Execução e entrega, nessa ordem.

**Ações:** execute cada pasta em ordem. Mostre a OS criada com CPF, placa e itens; diagnóstico; orçamento calculado com preços em centavos; rejeição, revisão e nova aprovação; consulta pelo cliente; execução com baixa; finalização e entrega. Destaque a lista de histórico ao final.

**Fala sugerida:** “A abertura identifica cliente por documento e veículo por placa, verifica o vínculo e registra a solicitação. A ordem começa como Recebida e passa a Em diagnóstico quando a equipe registra o diagnóstico. Os itens incluem serviço, peça ou insumo e copiam preço e unidade do catálogo. A apresentação calcula o orçamento automaticamente e muda a OS para Aguardando aprovação. Cada proposta é uma versão preservada. O cliente recebe uma credencial restrita à própria ordem, consulta o orçamento e registra sua decisão. Uma rejeição impede a execução; a equipe pode voltar ao diagnóstico e emitir uma nova versão.”

**Continue ao executar:** “Com a versão vigente aprovada e saldo suficiente, iniciar execução baixa todos os materiais e muda o status na mesma transação. A ordem passa por Em execução, Finalizada e Entregue. O histórico registra as transições e o cliente consulta o andamento pela API.”

**Nota de negócio:** a aprovação registra a decisão do orçamento; o status continua Aguardando aprovação até o início autorizado da execução. A disponibilização da proposta é por API e a credencial é entregue manualmente; não há envio automático de email ou WhatsApp.

## 09m20s a 10m30s Proteções e cenários de recusa

**Na tela:** resultados das chamadas negativas já executadas nas pastas anteriores e testes correspondentes.

**Ações:** destaque recusa de decisão sobre versão antiga, decisão repetida, início sem estoque e início repetido. Mostre que a ordem recusada e o histórico não sofreram uma baixa parcial. Cite a suíte de autenticação para token inválido, expirado e usuário sem permissão.

**Fala sugerida:** “A API impede aprovar versões antigas, repetir decisões e executar sem aprovação ou estoque. Repetir o início não consome materiais duas vezes. Se um item não tiver saldo, toda a operação é revertida. A consulta do cliente exige um token aleatório específico da OS; o banco guarda apenas seu hash e a credencial pode expirar, ser rotacionada ou revogada. CPF sozinho não autentica o cliente.”

**Controle de tempo:** mostre as evidências já obtidas; não repita pastas inteiras, pois usam dados e estados encadeados.

## 10m30s a 11m20s Listagem e indicador de tempo

**Na tela:** Listar OS, Consultar OS, histórico do cliente e pasta Indicadores.

**Ações:** consulte a OS entregue e execute `GET /api/reports/service-execution-time`.

**Fala sugerida:** “A equipe lista e detalha ordens, enquanto o cliente vê apenas a ordem autorizada. O indicador calcula a média entre início e finalização das ordens concluídas, incluindo as já entregues e excluindo a espera pela retirada. Em uma demonstração rápida, o tempo pode ser pequeno ou zero; o teste automatizado verifica também um intervalo controlado e o resultado quando não há ordens concluídas.”

## 11m20s a 13m25s Testes cobertura concorrência e segurança

**Na tela:** terminal e `docs/qualidade/fechamento-windows.md`, cobertura HTML e relatório de segurança.

**Ações:** rode `docker compose run --rm tests`; apresente a cobertura já gerada com o comando do guia. Rode `docker compose run --rm tests php scripts/verify-mysql-concurrency.php` e `docker compose exec -T app composer audit --locked`. Mostre os resumos e o relatório de análise estática.

**Fala sugerida:** “Os testes verificam regras unitárias e fluxos de integração. A suíte passou no Windows com 137 testes e 773 asserções. No container, os 136 testes aplicáveis passaram com 770 asserções; o caso específico do Windows é ignorado. A cobertura de aplicação é de aproximadamente 98,7%, e todos os domínios com linhas executáveis atingem 100%, acima do mínimo de 80%. Também executei 69 requisições e 93 asserções HTTP com a coleção Postman contra o MySQL.”

**Continue:** “Na validação concorrente, duas conexões disputam saldo suficiente para apenas uma OS. Em três rodadas houve exatamente um sucesso, uma rejeição, uma única baixa e nenhum saldo negativo. A auditoria Composer não apontou vulnerabilidades conhecidas nas dependências fixadas. O relatório registra o escopo e o resultado da análise estática do código; essas verificações não equivalem a um pentest.”

**Antes de gravar:** confira os totais nos relatórios da versão final. Se um teste ou scan falhar, corrija e atualize as evidências antes da gravação.

## 13m25s a 14m30s Versão e encerramento

**Na tela:** GitHub, README, documentação DDD, resultados da CI, branch protegida e terminal com hash.

**Ações:** mostre `git branch --show-current`, `git rev-parse HEAD` e `git status --short`. Exiba os links públicos de documentação e os arquivos de evidência. Mostre a integração via PR e o estado real das verificações, sem afirmar aprovação se estiverem pendentes.

**Fala sugerida:** “Este é o commit da versão demonstrada, que será o mesmo informado no PDF. O repositório reúne instruções de execução, OpenAPI, coleção Postman, modelagem DDD, exportações e relatórios. As mudanças chegam à main por Pull Request, com verificações de qualidade. O MVP cobre o fluxo completo da oficina, desde o cadastro e orçamento até a execução e entrega, preservando histórico e consistência do estoque. Obrigado.”

**Termine até 14m30s.** Use a margem apenas para pequenas pausas; não acrescente uma nova demonstração.

## Depois da gravação

1. Publique no YouTube com visibilidade **Público**, duração até 15 minutos e áudio compreensível.
2. Abra o vídeo e os links em janela sem login. Mantenha-os acessíveis durante a correção e revisão de notas.
3. Execute `powershell -ExecutionPolicy Bypass -File scripts/finalizar-entrega.ps1 -VideoUrl "https://www.youtube.com/watch?v=SEU_ID"` para gerar a cópia final clicável e atualizar o arquivo de vídeo do README.
4. Se publicar o link por um novo PR, registre o hash final e regenere o PDF. Mudanças apenas de documentação não alteram a aplicação demonstrada; os links devem apontar à versão efetivamente entregue.
5. Confira os campos da primeira página e envie apenas o PDF de entrega no canal da faculdade. O roteiro é material de apoio para você.

## Correspondência com o enunciado

| Exigência do vídeo | Trecho |
|---|---|
| Identificação e proposta | 00m00s a 00m40s |
| Arquitetura, camadas e justificativa do banco | 00m40s a 02m05s |
| Event Storming e principais elementos de DDD | 02m05s a 03m40s |
| Execução e OpenAPI | 03m40s a 04m30s |
| Autenticação e cadastros administrativos | 04m30s a 06m20s |
| Criação, serviços, materiais, orçamento, aprovação e seis status | 06m20s a 09m20s |
| Regras de transição e segurança | 09m20s a 10m30s |
| Listagem, detalhe e tempo médio | 10m30s a 11m20s |
| Testes, cobertura e vulnerabilidades | 11m20s a 13m25s |
| Versão, documentação pública e fechamento | 13m25s a 14m30s |
