# Finalizar o PDF acadêmico

O PDF de entrega tem campos editáveis para grupo, Discord, branch, hash e URL do vídeo. Nome, RM e tecnologias já estão preenchidos. Os dados de origem estão em `dados-entrega.json`; o roteiro está em `roteiro-apresentacao.md`.

## Inserir o vídeo após a gravação

Publique um vídeo real no YouTube como **Público**, com até 15 minutos. No PowerShell, na pasta do projeto:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/finalizar-entrega.ps1 -VideoUrl "https://www.youtube.com/watch?v=SEU_ID"
```

Use a URL real no lugar do exemplo. O script gera os PDFs em `output/pdf`, preenche o campo e acrescenta um link clicável para o vídeo. Também atualiza `docs/entrega/video.md` e `dados-entrega.json`; o README aponta para esse documento. Publique essa mudança por PR para que o endereço fique acessível aos avaliadores. Após integrar a documentação, execute novamente o script no commit final; ele preservará a URL e atualizará o hash no PDF.

O Python do runtime Codex é detectado automaticamente neste computador. Em outro ambiente, instale Python 3, `reportlab` e `pypdf`, e execute `python scripts/build-delivery-pdfs.py --video-url "URL_REAL" --save-video`. O gerador funciona em Windows, Linux e macOS com as fontes indicadas no código.

## Alterar diretamente no PDF

Abra o arquivo em um leitor que permita preencher formulários, como Edge ou Adobe Acrobat. Clique no campo de vídeo, digite a URL e salve uma cópia. Os campos permanecem interativos. Alterar o texto do campo não atualiza automaticamente a ação de um link já existente; para garantir que o link clicável aponte à nova URL, use o finalizador acima.

O PDF sem URL é a cópia preparada para você preencher, não a submissão final. Não envie à faculdade antes de inserir o vídeo público. Não compartilhe o ambiente Postman de `build/`, pois ele contém credenciais locais.

## Conferência antes de enviar

- Gabriel De Barros Neves, RM rm378693, Grupo 19 e Discord Nev3s na primeira página.
- Curso SOAT - Software Architecture, Fase 1, linguagem e tecnologias preenchidos.
- Branch e hash completo correspondem ao código demonstrado e publicado.
- Vídeo público, com áudio compreensível, duração até 15 minutos e URL clicável.
- Repositório, documentação DDD, exportações, OpenAPI e relatórios abrem sem login.
- Envie o arquivo `entrega-fiap-grupo-19.pdf` como o único PDF acadêmico. O roteiro é seu material de apoio.
