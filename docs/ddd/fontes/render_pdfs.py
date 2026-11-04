from pathlib import Path
import json, re, math, os
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A3, landscape
from reportlab.lib.colors import HexColor, Color, white
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import Paragraph
from reportlab.lib.styles import ParagraphStyle
from xml.sax.saxutils import escape

BASE=Path(__file__).resolve().parent.parent
TMP=BASE/'fontes'
C=json.loads((TMP/'event-storming.json').read_text(encoding='utf-8')); DS=json.loads((TMP/'diagramas.json').read_text(encoding='utf-8'))
def font_path(variable, candidates):
    if os.environ.get(variable): return os.environ[variable]
    return str(next(Path(path) for path in candidates if Path(path).exists()))
pdfmetrics.registerFont(TTFont('Body',font_path('PDF_FONT_REGULAR', ['C:/Windows/Fonts/arial.ttf', '/System/Library/Fonts/Supplemental/Arial.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'])))
pdfmetrics.registerFont(TTFont('Strong',font_path('PDF_FONT_BOLD', ['C:/Windows/Fonts/arialbd.ttf', '/System/Library/Fonts/Supplemental/Arial Bold.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'])))
W,H=landscape(A3); INK=HexColor('#173149'); MUTED=HexColor('#536779'); LIGHT=HexColor('#F3F6FA')
COLORS=['#FFE299','#A8DAFF','#FFD3A8','#D3BDFF','#B3EFBD']
def p(c,s,x,y,w,size=11,bold=False,color=INK,leading=None):
    st=ParagraphStyle('x',fontName='Strong' if bold else 'Body',fontSize=size,leading=leading or size*1.35,textColor=color)
    q=Paragraph(escape(s).replace('\n','<br/>'),st); _,h=q.wrap(w,1000)
    q.drawOn(c,x,H-y-h);return h
def box(c,s,x,y,w,h,color='#EAF2FB',size=12,bold=False):
    c.setFillColor(HexColor(color));c.setStrokeColor(HexColor('#C8D3DF'));c.roundRect(x,H-y-h,w,h,8,fill=1,stroke=1)
    ph=Paragraph(escape(s).replace('\n','<br/>'),ParagraphStyle('n',fontName='Strong' if bold else 'Body',fontSize=size,leading=size*1.35,textColor=INK,alignment=1))
    _,th=ph.wrap(w-22,h-12)
    if th>h-16:raise ValueError(f'Caixa pequena: {s}')
    ph.drawOn(c,x+11,H-y-(h+th)/2)
def line(c,a,b,label='',labelpos=None,dashed=False):
    x1,y1=a;x2,y2=b;c.setStrokeColor(MUTED);c.setLineWidth(1.2)
    if dashed:c.setDash(4,3)
    c.line(x1,H-y1,x2,H-y2);c.setDash()
    ang=math.atan2(y2-y1,x2-x1);n=6
    for da in [-.45,.45]:c.line(x2,H-y2,x2-n*math.cos(ang+da),H-y2+n*math.sin(ang+da))
    if label:
        xx,yy=labelpos or ((x1+x2)/2,(y1+y2)/2-8)
        lines=[]
        for chunk in label.split('\n'):
            words=chunk.split();s=''
            for word in words:
                if pdfmetrics.stringWidth((s+' '+word).strip(),'Body',10)>210 and s:lines.append(s);s=word
                else:s=(s+' '+word).strip()
            if s:lines.append(s)
        for j,t in enumerate(lines):
            tw=pdfmetrics.stringWidth(t,'Body',10)
            c.setFillColor(white);c.rect(xx-tw/2-4,H-yy-j*13-10,tw+8,14,fill=1,stroke=0)
            c.setFillColor(MUTED);c.setFont('Body',10);c.drawCentredString(xx,H-yy-j*13,t)
def header(c,k,title,status):
    c.setFillColor(INK);c.rect(0,H-12,W,12,fill=1,stroke=0)
    p(c,'OFICINA MECÂNICA / FASE 1',40,32,W-80,11,True,color=MUTED)
    p(c,f'{k}  |  {title}',40,60,W-80,27,True)
    p(c,status,40,104,W-80,11)
def footer(c,page,note=''):
    c.setStrokeColor(HexColor('#D7E0EA'));c.line(40,42,W-40,42)
    p(c,'03/11/2026 | Docker e MySQL validados | Documentação complementar',40,H-32,W-140,9,color=MUTED)
    p(c,str(page),W-85,H-32,45,9,True)
def notes(c,items,y=680):
    yy=y
    for s in items:
        yy+=p(c,s,40,yy,W-80,10)+9
    if yy>H-50:raise ValueError(f'Rodapé excedido: {yy}')

out=canvas.Canvas(str(BASE/'event-storming.pdf'),pagesize=(W,H));out.setTitle('Oficina - Event Storming');out.setAuthor('Documentação do projeto Oficina')
for pi,f in enumerate(C['flows'],1):
    header(out,f['id'],f['title'],f['subtitle'])
    x0=150;gap=10;cols=len(f['steps']);cw=(W-190-gap*(cols-1))/cols;rh=84;yy=168
    labels=['ATOR','COMANDO','EVENTO / VISÃO','POLÍTICA','AGREGADO']
    for row in range(5):
        p(out,labels[row],40,yy+30,100,10,True)
        for col,s in enumerate(f['steps']):
            color=COLORS[4] if row==2 and s[2].startswith('VISÃO') else COLORS[row]
            box(out,s[row],x0+col*(cw+gap),yy,cw,rh,color,size=10.2,bold=row==2)
        yy+=rh+12
    for col,s in enumerate(f['steps']):p(out,f'{col+1}. {s[5]}',x0+col*(cw+gap),yy+2,cw,8.8,color=MUTED)
    notes(out,f['notes'],y=yy+44)
    footer(out,pi);out.showPage()
out.save()

out=canvas.Canvas(str(BASE/'diagramas.pdf'),pagesize=(W,H));out.setTitle('Oficina - Diagramas de arquitetura e domínio');out.setAuthor('Documentação do projeto Oficina')
for pi,d in enumerate(DS,1):
    header(out,d['id'],d['title'],d['status'])
    if d['kind']=='native':
        nodes=d['nodes'];maxx=max(n[2]+n[4] for n in nodes);maxy=max(n[3]+n[5] for n in nodes)
        sx=(W-120)/maxx;sy=470/maxy;scale=min(sx,sy);origin=(W-maxx*scale)/2
        mp={}
        for ident,label,x,y,w,h,typ in nodes:mp[ident]=(origin+x*scale,160+y*scale,w*scale,h*scale,label,typ)
        for a,b,label in d['edges']:
            x,y,w,h,_,_=mp[a];xx,yy,ww,hh,_,_=mp[b]
            if abs(xx-x)>abs(yy-y):
                aa=(x+w,y+h/2) if xx>x else (x,y+h/2);bb=(xx,yy+hh/2) if xx>x else (xx+ww,yy+hh/2)
            else:aa=(x+w/2,y+h) if yy>y else (x+w/2,y);bb=(xx+ww/2,yy) if yy>y else (xx+ww/2,yy+hh)
            if d['id']=='D03' and a=='http' and b=='domain':
                top=190
                out.setStrokeColor(MUTED);out.line(aa[0],H-aa[1],aa[0],H-top);out.line(aa[0],H-top,bb[0],H-top)
                line(out,(bb[0],top),bb);p(out,label,390,top-18,350,10)
            else:line(out,aa,bb,label)
        for ident,(x,y,w,h,label,typ) in mp.items():
            box(out,label,x,y,w,h,{'actor':'#E6E6E6','proposed':'#E3D5FF','data':'#D6EEE9'}.get(typ,'#D4E8FA'),size=11.6,bold=False)
    elif d['id']=='D05':
        names=['Recebida','Em diagnóstico','Aguardando\naprovação','Em execução','Finalizada','Entregue']
        x0=52;cw=145;gap=39;y=330
        for i,name in enumerate(names):box(out,name,x0+i*(cw+gap),y,cw,95,'#D4E8FA',12,True)
        labs=['Iniciar\ndiagnóstico','Apresentar\norçamento','Aprovação\ne saldo','Finalizar','Entregar']
        for i,l in enumerate(labs):line(out,(x0+i*(cw+gap)+cw,y+47),(x0+(i+1)*(cw+gap),y+47));p(out,l,x0+i*(cw+gap)+cw-23,y+115,100,9)
        x1=x0+2*(cw+gap)+cw/2;x2=x0+(cw+gap)+cw/2
        out.setStrokeColor(MUTED);out.line(x1,H-y,x1,H-230);out.line(x1,H-230,x2,H-230);line(out,(x2,230),(x2,y));p(out,'Iniciar revisão da proposta',x2+20,205,300,11)
        box(out,'Aprovar ou rejeitar registra a decisão do orçamento.\nA decisão sozinha mantém o status Aguardando aprovação.',295,535,600,80,'#E3D5FF',12)
    elif d['id']=='D06':
        specs=[('customers\nid PK • document UK\ndocument_type • name',80,240,280,180),('vehicles\nid PK • customer_id FK\nplate UK • brand • model • year',640,240,410,180),('workshop_services\nid PK • name • price_cents',80,510,380,105),('users\nid PK • email UK\npassword • is_admin',640,510,410,105)]
        line(out,(360,330),(640,330),'1 cliente / 0..N veículos',labelpos=(500,305))
        for label,x,y,w,h in specs:box(out,label,x,y,w,h,'#D4E8FA',14)
    elif d['id']=='D07':
        # Layout hierárquico. As cardinalidades e restrições também estão nas fontes Mermaid.
        specs={
            'customers':(40,180,180,65),'vehicles':(320,180,180,65),'service_orders':(320,340,180,80),
            'order_status_history':(40,510,200,65),'service_order_items':(620,340,180,80),'workshop_services':(620,180,180,65),
            'materials':(950,180,190,80),'stock_movements':(940,510,200,80),
            'order_budgets':(320,510,190,80),'order_budget_items':(620,625,210,65)}
        edges=[('customers','vehicles','1 / 0..N'),('customers','service_orders','1 / 0..N'),('vehicles','service_orders','1 / 0..N'),('service_orders','service_order_items','1 / 0..N'),('workshop_services','service_order_items','0..1 / 0..N'),('materials','service_order_items','0..1 / 0..N'),('service_orders','order_budgets','1 / 0..N'),('order_budgets','order_budget_items','1 / 0..N'),('service_orders','order_status_history','1 / 0..N'),('materials','stock_movements','1 / 0..N'),('service_orders','stock_movements','0..1 / 0..N')]
        for a,b,label in edges:
            x,y,w,h=specs[a];xx,yy,ww,hh=specs[b]
            if abs(x-xx)>abs(y-yy):aa=(x+w,y+h/2) if xx>x else (x,y+h/2);bb=(xx,yy+hh/2) if xx>x else (xx+ww,yy+hh/2)
            else:aa=(x+w/2,y+h) if yy>y else (x+w/2,y);bb=(xx+ww/2,yy) if yy>y else (xx+ww/2,yy+hh)
            line(out,aa,bb,label)
        for label,(x,y,w,h) in specs.items():box(out,label,x,y,w,h,'#E3D5FF',10.5,True)
    else:
        src=d['source'];participants=re.findall(r'^ participant (\w+)',src,re.M);xs={k:110+i*((W-220)/(len(participants)-1)) for i,k in enumerate(participants)}
        msgs=re.findall(r'^ (\w+)(-->>|->>)(\w+): (.+)$',src,re.M)
        for name,x in xs.items():
            box(out,name,x-72,175,144,45,'#D4E8FA',12,True);out.setStrokeColor(HexColor('#B8C5D3'));out.setDash(4,4);out.line(x,H-220,x,H-655);out.setDash()
        spacing=390/max(len(msgs)-1,1)
        for j,(a,typ,b,label) in enumerate(msgs):
            y=250+j*spacing
            if a==b:
                x=xs[a];out.setStrokeColor(MUTED);out.line(x,H-y,x+45,H-y);out.line(x+45,H-y,x+45,H-y-18);line(out,(x+45,y+18),(x,y+18));p(out,label,x+60,y-8,250,10)
            else:line(out,(xs[a],y),(xs[b],y),label,labelpos=((xs[a]+xs[b])/2,y-9),dashed=typ=='-->>')
    notes(out,[d['note']],y=720)
    footer(out,pi);out.showPage()
out.save()
print('Gerados event-storming.pdf (3 páginas) e diagramas.pdf (9 páginas)')
