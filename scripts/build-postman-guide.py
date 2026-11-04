"""Gera colecoes publicas, manual detalhado, PDF e ZIP do Postman.

Nao le .env, credenciais locais ou relatorios privados do Newman.
"""
import copy
import hashlib
import json
import re
import textwrap
from pathlib import Path
from xml.sax.saxutils import escape
from zipfile import ZIP_DEFLATED, ZipFile

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import KeepTogether, PageBreak, Paragraph, Preformatted, SimpleDocTemplate, Spacer, Table, TableStyle

ROOT = Path(__file__).resolve().parent.parent
BASE = ROOT / 'docs/postman'
OUTPUT = ROOT / 'output/postman'
PDF = ROOT / 'output/pdf/guia-postman-completo.pdf'
SCHEMA = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'


def save(path, data):
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')


def flatten(items, folder='Cadastros iniciais'):
    for item in items:
        if 'item' in item:
            yield from flatten(item['item'], item['name'])
        else:
            yield folder, item


def scripts(item):
    return '\n'.join(line for e in item.get('event', []) for line in e['script']['exec'])


def status(item):
    found = re.search(r'\.status\((\d+)\)', scripts(item))
    return int(found.group(1)) if found else None


def auth(item):
    value = item['request'].get('auth', {})
    if value.get('type') == 'noauth':
        return 'No Auth', ''
    if value.get('type') == 'bearer':
        return 'Bearer Token {{customer_token}}', 'customer_token'
    return 'Herdar autenticação da coleção: Bearer {{access_token}}', 'access_token'


def objective(item):
    name = item['name'].split(' | ', 1)[-1]
    body = item['request'].get('body', {}).get('raw', '')
    code = status(item)
    if name == 'Abrir OS':
        return 'Crie a OS com serviço de R$ 50, peça de R$ 25 e 0,5 L de insumo a R$ 25/L. Confira Recebida, três itens e items_total_cents = 8750. Ainda não há baixa de estoque.'
    if name.startswith('Reativar peça'):
        return 'A peça foi inativada na demonstração do catálogo. Reative-a antes de incluir na OS; ela possui uma unidade em estoque.'
    if name == 'Apresentar versão 1':
        return 'Gere e disponibilize a proposta v1 de 8750 centavos. A OS muda para Aguardando aprovação. O orçamento é PENDING.'
    if name == 'Cliente rejeita proposta':
        return 'Com o token do cliente, rejeite a versão 2. A rejeição impede execução e preserva a proposta. A OS continua Aguardando aprovação.'
    if name == 'Cliente aprova proposta vigente':
        return 'Aprove a versão 3 usando a credencial do cliente. Confira decision = APPROVED. A decisão sozinha não inicia a execução.'
    if name == 'Zerar saldo da demonstração':
        return 'Saem os 1,5 L que restaram no insumo após entrada de 2,5 L e saída anterior de 1 L. O saldo fica zero para provocar a recusa de início.'
    if name == 'Recusar início sem estoque':
        return 'A ordem está aprovada, mas o insumo está sem saldo. Espere 409. A transação deve preservar o status e também a peça, sem consumo parcial.'
    if name == 'Repor estoque':
        return 'Registre entrada de 1 L. Agora há saldo para consumir 0,5 L, além da peça disponível.'
    if name == 'Iniciar execução aprovada':
        return 'Inicie a ordem aprovada. Confira Em execução; a peça passa de 1 para 0 unidade, e o insumo passa de 1 para 0,5 L. Baixa e status são atômicos.'
    if name == 'Conferir baixa ligada à OS':
        return 'Confira a única saída do insumo associada à OS, quantidade 0.500. O teste diferencia o consumo da OS dos ajustes manuais anteriores.'
    if name == 'Tempo médio de execução':
        return 'Confira orders_count maior que zero e average_seconds não negativo. O tempo pode ser zero numa execução rápida. Não inclui espera pela retirada.'
    if name == 'Emitir credencial da OS':
        return 'Como administrador, emita a credencial específica da OS. customer_token é salvo automaticamente. A resposta deve conter Cache-Control com no-store; não mostre o token na gravação.'
    if 'Obter token' in name:
        return 'Autentique a conta administrativa criada pelo Artisan. access_token é salvo automaticamente; o JWT desta versão dura 3600 segundos. Não exponha o valor no vídeo.'
    if code == 409:
        return 'Demonstre a regra de proteção indicada pelo nome da chamada. HTTP 409 é esperado e deve aprovar o teste; não tente corrigir essa recusa alterando o status diretamente.'
    if code == 422:
        return 'Repita intencionalmente o CPF já cadastrado. A API deve devolver erro de validação em errors.document. HTTP 422 é o resultado correto deste passo.'
    if code == 404:
        return 'Consulte o cliente que acabou de ser excluído. HTTP 404 confirma que ele não existe mais e é esperado neste passo.'
    if code == 204:
        return 'Exclua apenas o registro criado no bloco inicial. HTTP 204 significa sucesso sem corpo. A exclusão respeita os vínculos, por isso a ordem dos passos importa.'
    if 'inativar' in name:
        return 'Desative o material sem excluir seu histórico. Confira active = false. O material será reativado no bloco da OS quando necessário.'
    if 'entrada' in name:
        return 'Registre uma entrada IN com quantidade positiva e motivo. O saldo e o histórico devem aumentar juntos.'
    if 'saída' in name:
        return 'Registre uma saída OUT de uma unidade, preservando motivo e autoria. O saldo deve diminuir sem ficar negativo.'
    if 'histórico' in name:
        return 'Mostre entrada e saída na listagem paginada. Neste ponto da sequência, meta.total deve ser 2.'
    if 'versão' in name or 'versões' in name:
        return 'Confira o número da versão e a decisão da proposta. Revisões criam novos orçamentos; a proposta anterior continua consultável e não é sobrescrita.'
    if 'Cliente' in name or 'cliente acompanha' in name.lower() or name == 'Rejeição mantém etapa':
        return 'Consulte a visão do cliente com customer_token, restrito à própria OS. Observe status, proposta e histórico sem confundir essa credencial com o JWT administrativo.'
    if 'diagnóstico' in name:
        return 'Registre o diagnóstico da equipe e confira a transição Recebida para Em diagnóstico. O texto deve estar no corpo JSON.'
    if name == 'Finalizar serviços':
        return 'Finalize uma OS Em execução. Confira Finalizada e o instante finished_at, que encerra a medição do tempo de execução.'
    if name == 'Entregar veículo':
        return 'Registre a retirada da OS Finalizada. Confira Entregue e o instante delivered_at. A espera de retirada não altera o tempo de serviço.'
    if 'atualizar' in name.lower() or 'Editar' in name:
        return 'Mostre a atualização do cadastro. PUT envia os campos obrigatórios; PATCH altera apenas os campos enviados. Os preços históricos da OS são preservados.'
    if 'cadastrar' in name.lower():
        return 'Cadastre os dados fictícios do corpo e confira data.id. O script salva o ID para as próximas chamadas. Materiais novos começam com saldo zero.' if '/parts' in item['request']['url'] or '/supplies' in item['request']['url'] else 'Cadastre os dados fictícios do corpo e confira data.id. O ID será salvo automaticamente para manter o vínculo nas chamadas seguintes.'
    return 'Mostre os dados retornados e confira as asserções deste passo. Execute após os passos anteriores para que o ID, o saldo e o estado estejam corretos.'


def make_guided(original):
    result = copy.deepcopy(original)
    root_items = [item for item in result['item'] if 'request' in item]
    folders = [item for item in result['item'] if 'item' in item]
    result['item'] = [{'name': 'Cadastros iniciais', 'item': root_items}, *folders]
    extra = {
        'name': 'Reativar peça para OS',
        'request': {'method': 'PATCH', 'url': '{{base_url}}/api/parts/{{parts_id}}',
                    'header': [{'key': 'Accept', 'value': 'application/json'}, {'key': 'Content-Type', 'value': 'application/json'}],
                    'body': {'mode': 'raw', 'raw': '{"active": true}', 'options': {'raw': {'language': 'json'}}}},
        'event': [{'listen': 'test', 'script': {'type': 'text/javascript', 'exec': ['pm.test("Status 200", () => pm.response.to.have.status(200));']}}],
    }
    orders_folder = result['item'][2]
    insertion = next(i for i, item in enumerate(orders_folder['item']) if item['name'] == 'Abrir OS')
    orders_folder['item'].insert(insertion, extra)
    for _, item in flatten(result['item']):
        if item['name'] == 'Abrir OS':
            body = json.loads(item['request']['body']['raw'])
            body['items'].insert(1, {'kind': 'PART', 'catalog_id': '{{parts_id}}', 'quantity': '1'})
            item['request']['body']['raw'] = json.dumps(body, ensure_ascii=False, indent=2)
        for event in item.get('event', []):
            event['script']['exec'] = [line.replace('6250', '8750') for line in event['script']['exec']]
    for index, folder in enumerate(result['item'], 1):
        folder['name'] = f'{index:02} {folder["name"]}'
    for index, (_, item) in enumerate(flatten(result['item']), 1):
        item['name'] = f'{index:03} | ' + re.sub(r'^\d+\. ', '', item['name']).replace('—', '-')
        item['request']['description'] = (
            f'Passo {index:03}. Execute os anteriores na ordem.\n\n' + objective(item)
            + f'\n\nAutenticação: {auth(item)[0]}. Status esperado: {status(item)}.'
            + '\n\nA sequência pressupõe banco de ensaio inicialmente vazio. Scripts e IDs já configurados.'
        )
    result['info']['name'] = 'Oficina | Fluxo guiado completo | 70 chamadas'
    result['info']['description'] = 'Execute somente esta coleção no Runner, em ordem, com banco novo. Demonstra serviço + peça + insumo na mesma OS, total R$ 87,50. Use o ambiente Docker e preencha email/senha localmente. Os 70 passos possuem status esperado e orientação na descrição. Não publique tokens ou credenciais.'
    for variable in result['variable']:
        variable['value'] = 'http://127.0.0.1:8081' if variable['key'] == 'base_url' else ''
    return result


def route_url(path):
    if '{id}' not in path and '{budget}' not in path:
        return '{{base_url}}' + path
    resource = 'service-orders' if '/service-orders' in path else path.split('/')[2]
    key = {'customers': 'customer_id', 'vehicles': 'vehicle_id', 'services': 'service_id', 'parts': 'parts_id', 'supplies': 'supplies_id', 'service-orders': 'order_id'}[resource] if '{id}' in path else None
    return '{{base_url}}' + path.replace('{id}', '{{' + key + '}}' if key else '').replace('{budget}', '{{budget_id}}')


def sample_body(path, method):
    if path == '/api/auth/token': return {'email': '{{admin_email}}', 'password': '{{admin_password}}'}
    if method not in {'post', 'put', 'patch'}: return None
    if path.endswith('/diagnosis'): return {'diagnosis': 'Confirmada necessidade de troca'}
    if path.endswith('/decision'): return {'decision': 'APPROVED'}
    if path.endswith('/stock-movements'): return {'direction': 'IN', 'quantity': '2' if '/parts/' in path else '2.500', 'reason': 'Entrada de demonstração'}
    items = [
        {'kind': 'SERVICE', 'catalog_id': '{{service_id}}', 'quantity': '1'},
        {'kind': 'PART', 'catalog_id': '{{parts_id}}', 'quantity': '1'},
        {'kind': 'SUPPLY', 'catalog_id': '{{supplies_id}}', 'quantity': '0.5'},
    ]
    if path.endswith('/items'): return {'items': items}
    if path == '/api/service-orders': return {'customer_document': '11.222.333/0001-81', 'vehicle_plate': 'ZZZ9Z99', 'complaint': 'Troca de óleo e filtro', 'items': items}
    base = '/'.join(path.split('/')[:3])
    samples = {
        '/api/customers': {'name': 'Empresa de demonstração', 'document': '11222333000181', 'email': 'cliente@example.test'},
        '/api/vehicles': {'customer_id': '{{customer_id}}', 'plate': 'ZZZ9Z99', 'brand': 'Fiat', 'model': 'Uno', 'year': 2014},
        '/api/services': {'name': 'Diagnóstico e troca', 'price_cents': 5000},
        '/api/parts': {'name': 'Filtro', 'unit': 'unit', 'price_cents': 2500},
        '/api/supplies': {'name': 'Óleo', 'unit': 'L', 'price_cents': 2500},
    }
    if base not in samples or path.count('/') > 3: return None
    if method == 'patch': return {'active': False} if base in {'/api/parts', '/api/supplies'} else {'name': 'Cadastro atualizado'} if base != '/api/vehicles' else {'model': 'Uno atualizado'}
    return samples[base]


def make_reference(spec, original):
    result = {'info': {'name': 'Oficina | Referência de todas as rotas | Envio manual', 'schema': SCHEMA,
                       'description': 'Catálogo completo para consulta manual. NÃO execute inteiro no Runner. Configure IDs/tokens locais desta coleção; eles não são compartilhados automaticamente com a coleção guiada. Respeite o estado da OS, vínculos e saldo. Consulte a OpenAPI para schemas e respostas.'},
              'auth': copy.deepcopy(original['auth']), 'variable': copy.deepcopy(original['variable']), 'item': []}
    for v in result['variable']: v['value'] = 'http://127.0.0.1:8081' if v['key'] == 'base_url' else ''
    folders = {}
    operations = []
    public = [('/up', 'get', {'summary': 'Saúde da aplicação', 'security': [], 'responses': {'200': {'description': 'Aplicação saudável'}}}),
              ('/api/openapi', 'get', {'summary': 'Especificação OpenAPI', 'security': [], 'responses': {'200': {'description': 'Especificação JSON'}}})]
    all_ops = public + [(path, method, operation) for path, methods in spec['paths'].items() for method, operation in methods.items() if method in {'get', 'post', 'put', 'patch', 'delete'}]
    for path, method, operation in all_ops:
        category = 'Públicas' if path in {'/up', '/api/openapi', '/api/auth/token'} else 'Cliente da OS' if path.startswith('/api/customer/') else path.split('/')[2]
        folder = folders.setdefault(category, {'name': category, 'item': []})
        response_codes = list(operation.get('responses', {}))
        request = {'method': method.upper(), 'url': route_url(path), 'header': [{'key': 'Accept', 'value': 'application/json'}],
                   'description': 'ENVIO MANUAL. ' + operation.get('summary', '') + '\n\nRespostas documentadas: ' + ', '.join(response_codes) + '. Configure IDs locais e respeite as regras da OS. Exemplos de corpo são editáveis; não constituem um fluxo ordenado.'}
        security = operation.get('security', spec.get('security'))
        if security == []:
            request['auth'] = {'type': 'noauth'}
        elif path.startswith('/api/customer/'):
            request['auth'] = {'type': 'bearer', 'bearer': [{'key': 'token', 'value': '{{customer_token}}', 'type': 'string'}]}
        body = sample_body(path, method)
        if body is not None:
            request['header'].append({'key': 'Content-Type', 'value': 'application/json'})
            request['body'] = {'mode': 'raw', 'raw': json.dumps(body, ensure_ascii=False, indent=2), 'options': {'raw': {'language': 'json'}}}
        item = {'name': method.upper() + ' ' + path, 'request': request}
        key = None
        if method == 'post' and path.count('/') == 2:
            key = {'/api/customers': 'customer_id', '/api/vehicles': 'vehicle_id', '/api/services': 'service_id', '/api/parts': 'parts_id', '/api/supplies': 'supplies_id', '/api/service-orders': 'order_id'}.get(path)
        expression = 'pm.response.json().data.id'
        if path == '/api/auth/token': key, expression = 'access_token', 'pm.response.json().access_token'
        elif method == 'post' and path.endswith('/customer-access'): key, expression = 'customer_token', 'pm.response.json().data.access_token'
        elif method == 'post' and path.endswith('/budgets'): key = 'budget_id'
        if key:
            item['event'] = [{'listen': 'test', 'script': {'type': 'text/javascript', 'exec': [f'if (pm.response.code >= 200 && pm.response.code < 300) {{ pm.collectionVariables.set("{key}", {expression}); }}']}}]
        folder['item'].append(item)
        operations.append((method.upper(), path, response_codes, request))
    result['item'] = list(folders.values())
    return result, operations


def markdown(guided, operations):
    text = (BASE / 'preparacao-e-gravacao.md').read_text(encoding='utf-8')
    text += '\n# Catálogo dos 70 passos em ordem\n\nOs números abaixo são os mesmos dos nomes no Postman. Cabeçalhos JSON e seleção do ambiente seguem a preparação. As verificações listadas são os testes efetivamente configurados.\n'
    known = set()
    for folder, item in flatten(guided['item']):
        if folder not in known:
            text += '\n## ' + folder + '\n'
            known.add(folder)
        request = item['request']
        checks = re.findall(r'pm\.test\("([^"]+)"', scripts(item))
        saved = re.findall(r'collectionVariables\.set\("([^"]+)"', scripts(item))
        needed = sorted(set(re.findall(r'\{\{([^}]+)\}\}', json.dumps(request, ensure_ascii=False))) - {'base_url'})
        # Descricoes citam nomes de tokens; dependencias efetivas vem de URL/corpo/auth.
        actual = json.dumps({k: v for k, v in request.items() if k != 'description'}, ensure_ascii=False)
        needed = sorted(set(re.findall(r'\{\{([^}]+)\}\}', actual)) - {'base_url'})
        if auth(item)[1] and auth(item)[1] not in needed: needed.append(auth(item)[1])
        text += f'\n### {item["name"]}\n\n**Método e URL:** `{request["method"]} {request["url"]}`\n\n'
        text += '**Autenticação:** ' + auth(item)[0] + '.\n\n'
        text += '**Antes de enviar:** ' + (', '.join(needed) + ' devem estar preenchidos pelos passos anteriores ou por você no ambiente local.' if needed else 'API disponível e ambiente selecionado.') + '\n\n'
        text += '**O que fazer e explicar:** ' + objective(item) + '\n\n'
        body = request.get('body', {}).get('raw')
        if body:
            try: body = json.dumps(json.loads(body), ensure_ascii=False, indent=2)
            except json.JSONDecodeError: pass
            text += '**Body:** raw / JSON.\n\n```json\n' + body + '\n```\n\n'
        else: text += '**Body:** none, sem corpo.\n\n'
        text += f'**Status esperado:** HTTP {status(item)}.\n\n'
        text += '**Testes automáticos:** ' + '; '.join(checks) + '.\n\n'
        text += '**Variáveis salvas:** ' + (', '.join(saved) if saved else 'nenhuma nova variável') + '.\n\n'
    text += '\n# Todas as rotas para consulta manual\n\nSão 53 operações documentadas mais GET /up e GET /api/openapi. PUT e PATCH contam separadamente. Os corpos e as credenciais estão preparados na coleção de referência; IDs devem ser configurados nela.\n\n| Método | Rota | Respostas documentadas |\n|---|---|---|\n'
    for method, path, codes, request in operations: text += f'| {method} | {path} | {", ".join(codes)} |\n'
    text += '\n## Substituir todos os itens da OS\n\nEnvie PUT /api/service-orders/{{order_id}}/items apenas em Recebida ou Em diagnóstico. O corpo contém a lista completa, não somente o item novo:\n\n```json\n' + json.dumps(sample_body('/api/service-orders/{id}/items', 'put'), ensure_ascii=False, indent=2) + '\n```\n\n'
    text += 'Essa operação não faz parte dos 70 passos do Runner. Se usada depois do fluxo principal, a OS já entregue recusará a alteração com 409. Para exemplificar corretamente, use uma OS nova em diagnóstico ou revise uma proposta ainda aguardando aprovação.\n'
    text += '\n## Revogar e reemitir o acesso do cliente\n\nDELETE /api/service-orders/{{order_id}}/customer-access usa JWT administrativo e não tem corpo; o sucesso é 204. O token anterior deixa de autorizar consultas. POST no mesmo caminho emite uma nova credencial e substitui a anterior. Faça isso em um cenário adicional, para não invalidar o token durante a demonstração principal.\n'
    return text


def build_pdf(text):
    pairs = [('C:/Windows/Fonts/arial.ttf', 'C:/Windows/Fonts/arialbd.ttf'), ('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf')]
    regular, bold = next(p for p in pairs if Path(p[0]).exists())
    pdfmetrics.registerFont(TTFont('GuideBody', regular)); pdfmetrics.registerFont(TTFont('GuideStrong', bold))
    pdfmetrics.registerFontFamily('GuideBody', normal='GuideBody', bold='GuideStrong', italic='GuideBody', boldItalic='GuideStrong')
    ink, blue, pale = colors.HexColor('#172B43'), colors.HexColor('#164F83'), colors.HexColor('#F0F4F8')
    normal = ParagraphStyle('normal', fontName='GuideBody', fontSize=9.5, leading=13, textColor=ink, spaceAfter=7, allowWidows=0, allowOrphans=0)
    headings = {1: ParagraphStyle('title', parent=normal, fontName='GuideStrong', fontSize=20, leading=24, spaceAfter=15, keepWithNext=True),
                2: ParagraphStyle('section', parent=normal, fontName='GuideStrong', fontSize=14, leading=18, spaceBefore=8, spaceAfter=10, keepWithNext=True),
                3: ParagraphStyle('request', parent=normal, fontName='GuideStrong', fontSize=11, leading=15, spaceBefore=10, spaceAfter=8, keepWithNext=True)}
    code_style = ParagraphStyle('code', fontName='Courier', fontSize=9, leading=11.5, textColor=ink, spaceBefore=4, spaceAfter=10)
    class CodeBlock(Preformatted):
        def draw(self):
            self.canv.setFillColor(ink)
            super().draw()
    story, pending, code_lines, table_lines = [], [], [], []
    is_code = False
    def format_text(s):
        s = escape(s)
        s = re.sub(r'\*\*(.*?)\*\*', r'<b>\1</b>', s)
        return re.sub(r'`([^`]+)`', r'<font color="#164F83">\1</font>', s)
    def flush():
        if pending: story.append(Paragraph(format_text(' '.join(pending)), normal)); pending.clear()
    def flush_table():
        if not table_lines: return
        small = ParagraphStyle('table', parent=normal, fontSize=7.6, leading=10)
        head = ParagraphStyle('tablehead', parent=small, fontName='GuideStrong', textColor=colors.white)
        rows = [[Paragraph(escape(cell), head if i == 0 else small) for cell in row] for i, row in enumerate(table_lines)]
        table = Table(rows, colWidths=[45, 300, 154 - 45], repeatRows=1)
        table.setStyle(TableStyle([('BACKGROUND', (0, 0), (-1, 0), ink), ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, pale]), ('VALIGN', (0, 0), (-1, -1), 'TOP'), ('TOPPADDING', (0, 0), (-1, -1), 6), ('BOTTOMPADDING', (0, 0), (-1, -1), 6)]))
        story.append(table); table_lines.clear()
    for line in text.splitlines():
        if line.startswith('```'):
            flush()
            if is_code:
                wrapped = '\n'.join('\n'.join(textwrap.wrap(l, width=88, replace_whitespace=False, drop_whitespace=False)) if len(l) > 88 else l for l in code_lines)
                story.append(CodeBlock(wrapped, code_style)); code_lines.clear()
            is_code = not is_code
            continue
        if is_code: code_lines.append(line); continue
        if not line.startswith('|'): flush_table()
        if not line.strip(): flush(); continue
        if line.startswith('#'):
            flush(); level = len(line) - len(line.lstrip('#')); title = line[level:].strip()
            if level == 1 and story: story.append(PageBreak())
            story.append(Paragraph(escape(title), headings[min(level, 3)]))
        elif line.startswith('|'):
            flush()
            if re.fullmatch(r'[| :\-]+', line): continue
            table_lines.append([c.strip() for c in line.strip('|').split('|')])
        elif re.match(r'^\d+\. |^- ', line):
            flush(); story.append(Paragraph(format_text(line), normal))
        else: pending.append(line)
    flush(); flush_table()
    packed, request_block = [], []
    def flush_request():
        if request_block:
            packed.append(KeepTogether(list(request_block)))
            request_block.clear()
    for flow in story:
        request_start = isinstance(flow, Paragraph) and re.match(r'^\d{3} \|', flow.getPlainText())
        section_start = isinstance(flow, Paragraph) and flow.style.name in {'title', 'section'}
        if request_start or section_start or isinstance(flow, PageBreak): flush_request()
        if request_start and packed and isinstance(packed[-1], Paragraph) and packed[-1].style.name == 'section':
            request_block.append(packed.pop())
        if request_start or request_block:
            request_block.append(flow)
        else:
            packed.append(flow)
    flush_request()
    def footer(c, doc):
        c.setTitle('Guia completo do Postman - Oficina - Grupo 19')
        c.setAuthor('Gabriel De Barros Neves')
        c.setFont('GuideBody', 8); c.setFillColor(colors.HexColor('#536477'))
        c.drawString(45, 25, 'SOAT | Grupo 19 | Preparação, gravação e referência da API')
        c.drawRightString(A4[0] - 45, 25, str(doc.page))
    PDF.parent.mkdir(parents=True, exist_ok=True)
    document = SimpleDocTemplate(str(PDF), pagesize=A4, leftMargin=45, rightMargin=45, topMargin=40, bottomMargin=45)
    document.build(packed, onFirstPage=footer, onLaterPages=footer)


def main():
    OUTPUT.mkdir(parents=True, exist_ok=True)
    original = json.loads((BASE / 'oficina-cadastros.postman_collection.json').read_text(encoding='utf-8'))
    spec = json.loads((ROOT / 'docs/openapi.json').read_text(encoding='utf-8'))
    guided = make_guided(original)
    reference, operations = make_reference(spec, original)
    assert len(list(flatten(guided['item']))) == 70
    assert len(operations) == 55
    environment = {'name': 'Oficina Docker - preencher credenciais localmente', '_postman_variable_scope': 'environment',
                   'values': [{'key': 'base_url', 'value': 'http://127.0.0.1:8081', 'enabled': True, 'type': 'default'},
                              {'key': 'admin_email', 'value': '', 'enabled': True, 'type': 'default'},
                              {'key': 'admin_password', 'value': '', 'enabled': True, 'type': 'secret'}]}
    save(BASE / 'oficina-fluxo-guiado.postman_collection.json', guided)
    save(BASE / 'oficina-referencia-completa.postman_collection.json', reference)
    save(BASE / 'oficina-docker.postman_environment.json', environment)
    text = markdown(guided, operations)
    (BASE / 'guia-postman-completo.md').write_text(text, encoding='utf-8')
    build_pdf(text)
    contents = [BASE / n for n in ['oficina-fluxo-guiado.postman_collection.json', 'oficina-referencia-completa.postman_collection.json', 'oficina-docker.postman_environment.json', 'guia-postman-completo.md']]
    if (BASE / 'validacao-fluxo-guiado.json').exists(): contents.append(BASE / 'validacao-fluxo-guiado.json')
    with ZipFile(OUTPUT / 'oficina-postman-completo.zip', 'w', ZIP_DEFLATED) as z:
        for path in contents: z.write(path, path.name)
        z.write(ROOT / 'docs/openapi.json', 'openapi.json')
        z.write(PDF, PDF.name)
        z.writestr('LEIA-PRIMEIRO.txt', 'Importe oficina-fluxo-guiado.postman_collection.json e oficina-docker.postman_environment.json. Preencha email/senha localmente. Rode o fluxo em ordem com banco vazio. A colecao de referencia e para envio manual. Leia o PDF e o Markdown antes de gravar. O pacote nao contem credenciais reais.\n')
    print('Gerados fluxo guiado (70 chamadas), referencia (55 entradas), ambiente, manual, PDF e ZIP publico.')


if __name__ == '__main__':
    main()
