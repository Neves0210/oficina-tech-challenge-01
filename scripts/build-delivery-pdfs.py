"""Gera a entrega academica preenchivel e o roteiro em PDF.

Dependencias: reportlab e pypdf. Fontes editaveis em docs/entrega/.
"""
import argparse
import json
import re
import subprocess
from pathlib import Path
from urllib.parse import urlparse
from xml.sax.saxutils import escape

from pypdf import PdfReader
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas
from reportlab.platypus import Paragraph, SimpleDocTemplate, Table, TableStyle

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'output/pdf'
INK = colors.HexColor('#172B43')
MUTED = colors.HexColor('#536477')
ACCENT = colors.HexColor('#164F83')
PALE = colors.HexColor('#F0F4F8')
W, H = A4
MARGIN = 48
WIDTH = W - MARGIN * 2


def register_fonts():
    paths = [
        ('C:/Windows/Fonts/arial.ttf', 'C:/Windows/Fonts/arialbd.ttf'),
        ('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'),
        ('/System/Library/Fonts/Supplemental/Arial.ttf', '/System/Library/Fonts/Supplemental/Arial Bold.ttf'),
    ]
    regular, bold = next(pair for pair in paths if Path(pair[0]).exists() and Path(pair[1]).exists())
    pdfmetrics.registerFont(TTFont('Body', regular))
    pdfmetrics.registerFont(TTFont('Strong', bold))
    pdfmetrics.registerFontFamily('Body', normal='Body', bold='Strong', italic='Body', boldItalic='Strong')


def rich(text):
    value = escape(text)
    value = re.sub(r'\*\*(.*?)\*\*', r'<b>\1</b>', value)
    return re.sub(r'`([^`]+)`', r'<font color="#164F83">\1</font>', value)


class Delivery:
    def __init__(self, path):
        self.c = canvas.Canvas(str(path), pagesize=A4)
        self.c.setTitle('Oficina mecanica - Entrega da Fase 1 - Grupo 19')
        self.c.setAuthor('Gabriel De Barros Neves')
        self.page = 0

    def new_page(self, title, subtitle=None):
        if self.page:
            self.finish_page()
        self.page += 1
        self.y = H - MARGIN
        self.p('FIAP  /  SOAT  /  FASE 1  /  GRUPO 19', size=9, bold=True, color=MUTED)
        self.y -= 12
        self.p(title, size=24, bold=True, leading=29, gap=12)
        if subtitle:
            self.p(subtitle, size=11, color=MUTED, gap=15)

    def finish_page(self):
        self.c.setFont('Body', 8)
        self.c.setFillColor(MUTED)
        self.c.drawString(MARGIN, 29, 'Gabriel De Barros Neves | rm378693 | Grupo 19')
        self.c.drawRightString(W - MARGIN, 29, f'{self.page} / 5')
        self.c.showPage()

    def p(self, text, size=10.5, bold=False, color=INK, leading=None, gap=9):
        style = ParagraphStyle('body', fontName='Strong' if bold else 'Body', fontSize=size,
                               leading=leading or size * 1.42, textColor=color, splitLongWords=True)
        p = Paragraph(text, style)
        _, height = p.wrap(WIDTH, H)
        if self.y - height < 58:
            raise ValueError(f'Conteudo excede a pagina {self.page}: {text[:80]}')
        p.drawOn(self.c, MARGIN, self.y - height)
        self.y -= height + gap

    def h(self, text):
        self.y -= 7
        self.p(text, size=13, bold=True, gap=9)

    def link(self, label, url):
        self.p(escape(label), bold=True, size=10, gap=3)
        self.p(f'<link href="{escape(url, {chr(34): "&quot;"})}" color="#164F83">{escape(url)}</link>', size=9, leading=12, gap=12)

    def field(self, name, label, value, height=29, size=10, multiline=False):
        self.p(label, size=9, bold=True, color=MUTED, gap=5)
        bottom = self.y - height
        if bottom < 60:
            raise ValueError('Campo fora da pagina')
        self.c.acroForm.textfield(name=name, tooltip=label, value=value, x=MARGIN, y=bottom,
                                 width=WIDTH, height=height, fontName='Helvetica', fontSize=size,
                                 borderStyle='solid', borderWidth=0.6, borderColor=colors.HexColor('#B6C5D4'),
                                 fillColor=PALE, textColor=INK, forceBorder=True,
                                 fieldFlags='multiline' if multiline else '', maxlen=400)
        self.y = bottom - 13

    def table(self, rows, widths=None):
        style = ParagraphStyle('cell', fontName='Body', fontSize=9.5, leading=13.2, textColor=INK)
        header_style = ParagraphStyle('head', parent=style, fontName='Strong', textColor=colors.white)
        cells = [[Paragraph(escape(str(cell)), header_style if index == 0 else style) for cell in row] for index, row in enumerate(rows)]
        table = Table(cells, colWidths=widths or [WIDTH * 0.36, WIDTH * 0.64], hAlign='LEFT')
        table.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), INK), ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, PALE]),
            ('VALIGN', (0, 0), (-1, -1), 'TOP'), ('LEFTPADDING', (0, 0), (-1, -1), 9),
            ('RIGHTPADDING', (0, 0), (-1, -1), 9), ('TOPPADDING', (0, 0), (-1, -1), 8),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
        ]))
        _, height = table.wrap(WIDTH, H)
        if self.y - height < 58:
            raise ValueError(f'Tabela excede pagina {self.page}')
        table.drawOn(self.c, MARGIN, self.y - height)
        self.y -= height + 15

    def save(self):
        self.finish_page()
        self.c.save()


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT, text=True, encoding='utf-8').strip()


def create_delivery(data, branch, commit, video):
    target = OUT / 'entrega-fiap-grupo-19.pdf'
    d = Delivery(target)
    repo = data['repositorio']
    blob = repo + '/blob/' + commit + '/'
    d.new_page('Oficina mecânica', 'Documento de entrega do Tech Challenge')
    d.p('MVP de backend para organizar atendimento, orçamento, execução e entrega de serviços, com acompanhamento do cliente e controle consistente de materiais.')
    d.h('Identificação da entrega')
    d.p(f"<b>Curso:</b> {escape(data['curso'])}<br/><b>Fase:</b> {escape(data['fase'])}<br/><b>Integrante:</b> {escape(data['nome'])}<br/><b>RM:</b> {escape(data['rm'])}", leading=17)
    d.field('grupo', 'Número do grupo', data['grupo'], height=25)
    d.field('discord', 'Username no Discord', data['discord'], height=25)
    d.p('<b>Linguagem e tecnologias:</b> PHP 8.4+, Laravel 13, MySQL 8.4, JWT, PHPUnit, PCOV, Docker Compose, Nginx, OpenAPI e Postman.', size=10)
    d.field('branch', 'Branch da versão entregue', branch, height=25)
    d.field('commit', 'Hash completo do commit da entrega', commit, height=25, size=9)
    d.field('video_url', 'Link completo do vídeo público no YouTube', video, height=37, size=9, multiline=True)
    if video:
        d.p(f'<link href="{escape(video)}" color="#164F83"><b>Abrir vídeo publicado no YouTube</b></link>', size=10)
    else:
        d.p('Preencha após publicar o vídeo. O campo é editável; use o finalizador para gerar também o link clicável.', size=9, color=MUTED)
    d.p('A avaliação deve considerar a versão identificada acima, correspondente à aplicação demonstrada no vídeo.', size=9, color=MUTED)

    d.new_page('Repositório e execução', 'Código fonte e decisões técnicas da solução')
    d.link('Repositório utilizado na solução final', repo)
    d.link('Código da versão identificada na primeira página', repo + '/tree/' + commit)
    d.link('README com preparação, autenticação e testes', blob + 'README.md')
    d.link('Guia de execução Docker e demonstração com Postman', blob + 'docs/entrega/docker-windows.md')
    d.h('Arquitetura do monólito')
    d.table([
        ['Camada', 'Responsabilidade'],
        ['Http', 'Controllers, validação das requisições, rotas e middleware.'],
        ['Application', 'Coordenação dos casos de uso e das operações da oficina.'],
        ['Domain', 'Regras, objetos de valor, estados e contratos de repositório.'],
        ['Infrastructure', 'Persistência Eloquent, transações, bloqueios e tokens.'],
    ])
    d.p('O AppServiceProvider conecta contratos às implementações. A organização mantém um único backend e um único banco transacional, sem microserviços ou mensageria de negócio.')
    d.h('Justificativa do MySQL')
    d.p('O domínio possui vínculos entre clientes, veículos, OS, orçamentos e materiais. Integridade referencial e transações InnoDB preservam esses vínculos; bloqueios de linha coordenam a disputa pelo estoque. SQLite apoia a suíte rápida e a cobertura, enquanto o fluxo e a concorrência foram validados também no MySQL.')

    d.new_page('Modelagem de domínio', 'Documentação DDD e exportações da versão entregue')
    d.link('Documentação online dos três fluxos de Event Storming', blob + 'docs/ddd/event-storming.md')
    d.link('Diagramas online de arquitetura e domínio', blob + 'docs/ddd/catalogo-de-diagramas.md')
    d.link('Linguagem ubíqua e glossário', blob + 'docs/ddd/linguagem-ubiqua.md')
    d.link('Exportação do Event Storming em PDF', blob + 'docs/ddd/event-storming.pdf')
    d.link('Exportação dos diagramas em PDF', blob + 'docs/ddd/diagramas.pdf')
    d.h('Elementos aplicados na implementação')
    d.p('<b>Fluxos:</b> criação da OS; acompanhamento e execução; gestão de peças, insumos e estoque. A documentação relaciona atores, comandos, eventos conceituais, políticas e agregados às classes implementadas.')
    d.p('<b>Entidades:</b> Cliente, Veículo, Ordem de Serviço, Material e movimentações. <b>Agregados:</b> OS protege itens e versões de orçamento; estoque protege saldo e movimentações. Os contextos são limites lógicos no monólito.')
    d.p('<b>Objetos de valor e estados:</b> DocumentNumber, Plate, Money, Quantity, OrderStatus e BudgetDecision. Dinheiro é tratado em centavos, e quantidades têm representação decimal exata.')
    d.p('Os documentos online e suas exportações descrevem a implementação vigente. O FigJam anterior permanece identificado como histórico; a referência para avaliação é a documentação versionada acima.', size=9.5, color=MUTED)

    d.new_page('Funcionalidades entregues', 'Fluxos administrativos e acompanhamento do cliente')
    d.table([
        ['Funcionalidade', 'Comportamento implementado'],
        ['Clientes e veículos', 'CRUD, CPF/CNPJ e placa validados, normalizados e únicos; verificação de vínculo.'],
        ['Serviços e materiais', 'CRUD de serviços, peças e insumos; preços em centavos; entradas, saídas e histórico.'],
        ['Criação da OS', 'Identificação por documento e placa; solicitação, diagnóstico e itens com preços copiados.'],
        ['Orçamento', 'Cálculo automático, versões preservadas, disponibilização por API e aprovação ou rejeição.'],
        ['Acesso do cliente', 'Credencial específica da própria OS, com validade, rotação, revogação e hash persistido.'],
        ['Execução e estoque', 'Aprovação vigente e saldo exigidos; baixa e status na mesma transação; rollback em falhas.'],
        ['Acompanhamento', 'Lista e detalhe administrativos; consulta do cliente e histórico das transições.'],
        ['Indicador', 'Tempo médio entre início e finalização de ordens concluídas, sem incluir espera pela entrega.'],
    ])
    d.h('Seis status controlados')
    d.p('Recebida → Em diagnóstico → Aguardando aprovação → Em execução → Finalizada → Entregue.')
    d.p('A rejeição impede a execução e permite revisão com retorno ao diagnóstico. Aprovar registra a decisão do orçamento; o início autorizado da execução realiza a próxima transição e a baixa de materiais.')
    d.h('Segurança das APIs')
    d.p('JWT protege os recursos administrativos. O cliente usa token aleatório restrito à OS; CPF isolado não autoriza acesso. A disponibilização do orçamento ocorre por API e a credencial é entregue manualmente. O MVP é exclusivamente backend.')
    d.link('Especificação OpenAPI da versão entregue', blob + 'docs/openapi.json')

    evidence = json.loads((ROOT / 'docs/qualidade/evidencias/windows-final/ambiente.json').read_text(encoding='utf-8'))
    covered = evidence['coverage']['covered']
    lines = evidence['coverage']['lines']
    percent = f'{covered / lines * 100:.2f}'.replace('.', ',')
    d.new_page('Qualidade e segurança', 'Evidências reproduzíveis da validação final')
    d.table([
        ['Verificação', 'Resultado registrado'],
        ['Testes no Windows', '137 aprovados; 773 asserções.'],
        ['Testes no MySQL Docker', '136 aprovados; 770 asserções; 1 caso exclusivo do Windows ignorado.'],
        ['Cobertura de app/', f'{covered}/{lines} linhas; {percent}%. Domínios executáveis: 100%; mínimo exigido: 80%.'],
        ['Fluxo HTTP em MySQL', '69 chamadas; 93 asserções; nenhuma falha.'],
        ['Concorrência de estoque', 'Três rodadas: uma OS inicia, outra é recusada; uma saída, saldo zero e rollback comprovado.'],
        ['Composer audit', 'Nenhuma vulnerabilidade conhecida ou pacote abandonado na consulta.'],
        ['Semgrep p/php', '23 regras; 84 arquivos PHP; zero achados e zero erros.'],
    ])
    d.link('Relatório final de testes e evidências permanentes', blob + 'docs/qualidade/fechamento-windows.md')
    d.link('Relatório da análise de vulnerabilidades', blob + 'docs/seguranca/fechamento-windows.md')
    d.link('Rastreabilidade entre requisitos e implementação', blob + 'docs/arquitetura/requisitos-e-rastreabilidade.md')
    d.p('A auditoria verifica avisos publicados das dependências, e o scan estático cobre as regras PHP aplicadas. Os resultados não equivalem a pentest ou garantia de ausência de vulnerabilidades desconhecidas.', size=9.5)
    d.p('O repositório e a documentação estão públicos. O vídeo deve ser publicado como Público no YouTube, ter até 15 minutos e permanecer acessível durante a correção e a revisão de notas.', size=9.5)
    d.save()
    fields = PdfReader(target).get_fields()
    expected = {'grupo': data['grupo'], 'discord': data['discord'], 'branch': branch, 'commit': commit, 'video_url': video}
    for key, value in expected.items():
        if fields[key].get('/V') != value:
            raise ValueError(f'Valor incorreto no formulario: {key}')
    widgets = [a.get_object() for page in PdfReader(target).pages for a in page.get('/Annots', []) if a.get_object().get('/Subtype') == '/Widget']
    if len(widgets) != len(expected) or any(not w.get('/AP', {}).get('/N') for w in widgets):
        raise ValueError('Aparencia ou arvore de campos PDF incompleta')
    return target


def create_script_pdf():
    target = OUT / 'roteiro-apresentacao-14m30s.pdf'
    styles = getSampleStyleSheet()
    for name, size, leading in [('Normal', 10, 14), ('Title', 23, 28), ('Heading1', 15, 19), ('Heading2', 12, 16)]:
        styles[name].fontName = 'Strong' if name != 'Normal' else 'Body'
        styles[name].fontSize = size
        styles[name].leading = leading
        styles[name].textColor = INK
        styles[name].spaceAfter = 9
    styles['Title'].textColor = colors.black
    styles['Normal'].alignment = TA_LEFT
    styles['Normal'].splitLongWords = True
    story = []
    code = False
    pending = []
    table_rows = []

    def flush():
        if pending:
            story.append(Paragraph(rich(' '.join(pending)), styles['Normal']))
            pending.clear()

    def flush_table():
        if not table_rows:
            return
        normal = ParagraphStyle('matrix', parent=styles['Normal'], fontSize=9, leading=12)
        head = ParagraphStyle('matrix_head', parent=normal, fontName='Strong', textColor=colors.white)
        cells = [[Paragraph(rich(cell), head if i == 0 else normal) for cell in row] for i, row in enumerate(table_rows)]
        matrix = Table(cells, colWidths=[WIDTH * 0.66, WIDTH * 0.34], repeatRows=1)
        matrix.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), INK),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, PALE]),
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
            ('LEFTPADDING', (0, 0), (-1, -1), 8), ('RIGHTPADDING', (0, 0), (-1, -1), 8),
            ('TOPPADDING', (0, 0), (-1, -1), 7), ('BOTTOMPADDING', (0, 0), (-1, -1), 7),
        ]))
        story.append(matrix)
        table_rows.clear()

    for line in (ROOT / 'docs/entrega/roteiro-apresentacao.md').read_text(encoding='utf-8').splitlines():
        if not line.startswith('|'):
            flush_table()
        if line.startswith('```'):
            flush()
            code = not code
            continue
        if not line.strip():
            flush()
        elif line.startswith('# '):
            flush()
            story.append(Paragraph(escape(line[2:]), styles['Title']))
        elif line.startswith('## '):
            flush()
            story.append(Paragraph(escape(line[3:]), styles['Heading1']))
        elif line.startswith('|'):
            flush()
            if set(line.replace('|', '').replace(' ', '')) == {'-'}:
                continue
            table_rows.append([part.strip() for part in line.strip('|').split('|')])
        elif code or re.match(r'^\d+\. ', line):
            flush()
            story.append(Paragraph(rich(line), styles['Normal']))
        else:
            pending.append(line)
    flush()
    flush_table()

    def footer(c, document):
        c.setTitle('Roteiro da apresentacao - Oficina - 14 minutos e 30 segundos')
        c.setAuthor('Gabriel De Barros Neves')
        c.setFont('Body', 8)
        c.setFillColor(MUTED)
        c.drawString(MARGIN, 26, 'SOAT | Fase 1 | Grupo 19 | Material de apoio para a gravação')
        c.drawRightString(W - MARGIN, 26, str(document.page))

    document = SimpleDocTemplate(str(target), pagesize=A4, leftMargin=MARGIN, rightMargin=MARGIN,
                                 topMargin=44, bottomMargin=48, allowSplitting=0)
    document.build(story, onFirstPage=footer, onLaterPages=footer)
    return target


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--video-url', default=None)
    parser.add_argument('--commit', default=None)
    parser.add_argument('--branch', default=None)
    parser.add_argument('--save-video', action='store_true')
    args = parser.parse_args()
    data_path = ROOT / 'docs/entrega/dados-entrega.json'
    data = json.loads(data_path.read_text(encoding='utf-8'))
    commit = args.commit or git('rev-parse', 'HEAD')
    branch = args.branch or git('branch', '--show-current')
    if not re.fullmatch(r'[a-fA-F0-9]{40}', commit):
        raise ValueError('Informe um hash completo de 40 caracteres')
    video = args.video_url if args.video_url is not None else data['video_url']
    if video:
        parsed = urlparse(video)
        if parsed.scheme != 'https' or parsed.hostname not in {'youtube.com', 'www.youtube.com', 'youtu.be'}:
            raise ValueError('Informe a URL HTTPS real do video no YouTube')
    if args.save_video and video:
        data['video_url'] = video
        data_path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
        (ROOT / 'docs/entrega/video.md').write_text(
            '# Vídeo da entrega\n\n'
            f'[Assistir ao vídeo público no YouTube]({video})\n\n'
            'Apresentação de Gabriel De Barros Neves, rm378693, Grupo 19.\n'
            'A branch e o hash da versão demonstrada estão no PDF de entrega.\n', encoding='utf-8')
    OUT.mkdir(parents=True, exist_ok=True)
    register_fonts()
    for path in [create_delivery(data, branch, commit, video), create_script_pdf()]:
        print(f'{path.name}: {len(PdfReader(path).pages)} paginas')


if __name__ == '__main__':
    main()
