"""
エラーコード一覧のPDFを、テーマで使うPHPの配列に変えます。

  python3 parse_ts.py <入力.pdf> <出力.php> <メーカー名>

PDFは、ブラウザで「印刷 → PDFに保存」したものを想定しています。

★行の区切りは「対処方法」という札で見つけています。
  コードの欄は「E00～24」のように2行になったり、
  「おふろの栓が抜けていませんか」のように文章のこともあるので、
  コードの見た目では区切れないためです。
"""
import re, sys, subprocess
from xml.etree import ElementTree as ET

pdf, out_php, maker = sys.argv[1], sys.argv[2], sys.argv[3]
xml = '/tmp/_ts_tmp.xml'
subprocess.run(['pdftotext', '-bbox-layout', pdf, xml], check=True)

src = open(xml, encoding='utf-8').read().replace(' xmlns="http://www.w3.org/1999/xhtml"', '')
root = ET.fromstring(src[src.index('<html'):])

lines = []
for pi, pg in enumerate(root.iter('page')):
    ph = float(pg.get('height'))
    for ln in pg.iter('line'):
        y = float(ln.get('yMin'))
        if y < 45 or y > ph - 45: continue
        ws = [(float(w.get('xMin')), (w.text or '')) for w in ln.iter('word')]
        if not ws: continue
        t = ''.join(w[1] for w in ws).strip()
        if t: lines.append((pi, y, min(w[0] for w in ws), t))
lines.sort(key=lambda r: (r[0], r[1]))

STOP = ('Copyright', '会社概要', 'プライバシーポリシー', '職人募集')
HEAD = {'コード', '内容・原因'}
def col(x): return 'code' if x < 110 else ('mid' if x < 340 else 'fix')

# 見出しの行（コード｜内容・原因｜対処方法）の y を覚えて、区切りから外します
head_y = {(pi, round(y)) for pi, y, x, t in lines if t in HEAD}

body = []
for pi, y, x, t in lines:
    if any(s in t for s in STOP): break
    if t.startswith('ホーム▶'): continue
    body.append((pi, y, x, t))

# 「対処方法」の札が出るところが、1件のはじまりです
starts = [(pi, y) for pi, y, x, t in body
          if col(x) == 'fix' and t == '対処方法'
          and (pi, round(y)) not in head_y
          and not any((pi, round(y + d)) in head_y for d in (-2, -1, 1, 2))]

rows = [{'pi': pi, 'y': y, 'code': [], 'mid': [], 'fix': []} for pi, y in starts]

def owner(pi, y):
    best = None
    for r in rows:
        if (r['pi'], r['y'] - 4) <= (pi, y): best = r
        else: break
    return best

for pi, y, x, t in body:
    if t in HEAD or t == maker: continue
    r = owner(pi, y)
    if r is None: continue
    c = col(x)
    if c == 'code':
        r['code'].append(t)
    elif c == 'mid':
        r['mid'].append(t)
    else:
        if t != '対処方法': r['fix'].append(t)

def join(parts):
    buf = ''
    for p in parts:
        if buf and re.match(r'^[①②③④⑤⑥⑦⑧⑨⑩・※]', p): buf += '\n'
        buf += p
    return buf.strip()

out = []
for r in rows:
    title, cause, incause = '', [], False
    for t in r['mid']:
        if t == '原因': incause = True; continue
        if not incause and not title: title = t
        else: cause.append(t)
    out.append({'code': ''.join(r['code']).strip(), 'title': title,
                'cause': join(cause), 'fix': join(r['fix'])})

def q(s):
    return '"' + s.replace('\\', '\\\\').replace('"', '\\"').replace('$', '\\$').replace('\n', '\\n') + '"'

with open(out_php, 'w', encoding='utf-8') as f:
    f.write('<?php\n/**\n * ' + maker + 'のエラーコード一覧\n *\n')
    f.write(' * もとの資料：旧サイトの同じページを印刷したPDF。\n')
    f.write(' * このファイルは自動で作っています。直すときは、下の配列を直してください。\n */\n\n')
    f.write("if ( ! defined( 'ABSPATH' ) ) exit;\n\nreturn array(\n")
    for r in out:
        f.write('\tarray(\n')
        for k in ('code', 'title', 'cause', 'fix'):
            f.write("\t\t'%s'%s => %s,\n" % (k, ' ' * (5 - len(k)), q(r[k])))
        f.write('\t),\n')
    f.write(');\n')

print(f'{len(out)}件 → {out_php}')
bad = [r['code'] for r in out if not r['code'] or not r['fix']]
if bad: print('  ※要確認:', bad)
