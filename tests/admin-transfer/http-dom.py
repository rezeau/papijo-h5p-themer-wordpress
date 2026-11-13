"""Real multipart uploads/POST/download boundary plus static form DOM checks. No CMS."""
import html.parser
import json
import os
import pathlib
import secrets
import socket
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[2]
PHP = sys.argv[1]
checks = 0

def check(value, message):
    global checks
    if not value:
        raise AssertionError(message)
    checks += 1

class Forms(html.parser.HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.forms = []; self.current = None; self.ids = {}; self.buttons = []; self.scripts = 0
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'script': self.scripts += 1
        if tag == 'form':
            check(self.current is None, 'No nested forms')
            self.current = {'attrs': a, 'values': [], 'inputs': []}; self.forms.append(self.current)
            if 'id' in a: self.ids[a['id']] = self.current
        if tag == 'input' and self.current is not None:
            self.current['inputs'].append(a)
            if a.get('type') == 'hidden': self.current['values'].append((a['name'], a.get('value', '')))
        if tag == 'button' and 'form' in a: self.buttons.append(a)
    def handle_endtag(self, tag):
        if tag == 'form': self.current = None
    def operation(self, op, mode=None):
        for form in self.forms:
            fields = dict(form['values'])
            if fields.get('pjh5pwp_op') == op and (mode is None or fields.get('mode') == mode): return form
        raise AssertionError('Missing form ' + op)

pages = json.loads(subprocess.check_output([PHP, '-n', 'tests/admin-transfer/run.php', '--render'], cwd=ROOT))
for name, markup in pages.items():
    dom = Forms(); dom.feed(markup)
    check(dom.scripts == 0, 'Untrusted names/descriptions never become script elements')
    if name == 'overview':
        check(len(dom.buttons) >= 3, 'Custom rows have native external-form buttons')
        for button in dom.buttons:
            check(button['form'] in dom.ids, 'Export button references an existing separate form')
            check(dict(dom.ids[button['form']]['values'])['mode'] == 'single', 'External button targets single export')
        for form in dom.forms:
            f = dict(form['values'])
            if f.get('pjh5pwp_op') == 'export' and f.get('mode') == 'single': check(f['id'] not in ('daylight', 'dark'), 'Builtin rows excluded')
    if name == 'upload':
        f = dom.operation('import_preview')
        check(f['attrs']['enctype'] == 'multipart/form-data', 'Real upload encoding')
        check(any(i.get('type') == 'file' and i.get('id') == 'pjh5pwp-theme-json' for i in f['inputs']), 'Labeled file input')
    if name == 'preview':
        check(any(i.get('name') == 'confirm' and 'required' in i for i in dom.operation('import_commit')['inputs']), 'Explicit confirmation required')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None
opener = urllib.request.build_opener(NoRedirect)
sock = socket.socket(); sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]; sock.close()
key = secrets.token_hex(24)
base = f'http://127.0.0.1:{port}/http-fixture.php?key={key}'
env = dict(os.environ, PJH5PWP_UI_TEST_KEY=key)
proc = subprocess.Popen([PHP, '-n', '-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'tests/admin-transfer')], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

def request(fields=None, upload=None, suffix=''):
    data = None; headers = {}
    if fields is not None:
        if upload is None:
            data = urllib.parse.urlencode(fields).encode(); headers['Content-Type'] = 'application/x-www-form-urlencoded'
        else:
            boundary = 'fixture-' + secrets.token_hex(12); chunks = []
            for k, v in fields:
                chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="theme_json"; filename="colors.json"\r\nContent-Type: application/json\r\n\r\n'.encode() + upload + b'\r\n')
            chunks.append(f'--{boundary}--\r\n'.encode()); data = b''.join(chunks); headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
    try: response = opener.open(urllib.request.Request(base + suffix, data=data, headers=headers), timeout=10)
    except urllib.error.HTTPError as error: response = error
    return response.status, response.headers, response.read()

try:
    for _ in range(50):
        try:
            status, _, body = request()
            break
        except urllib.error.URLError: time.sleep(.1)
    else: raise AssertionError('Fixture server unavailable')
    check(status == 200, 'Existing admin page responds without live CMS')
    dom = Forms(); dom.feed(body.decode()); export = dom.operation('export', 'single')['values']
    status, headers, body = request(export)
    check(status == 200 and headers.get_content_type() == 'application/json', 'Actual download MIME')
    check(headers.get('Content-Disposition') == 'attachment; filename="papijo-h5p-theme-colors-existing.json"', 'Actual safe attachment header')
    check(headers.get('Cache-Control') == 'private, no-store' and headers.get('X-Content-Type-Options') == 'nosniff', 'Actual private/nosniff headers')
    payload = json.loads(body); check('density' not in payload['themes'][0], 'Actual downloaded JSON excludes Density')
    status, _, _ = request(export, suffix='&deny=1'); check(status == 403, 'Unauthorized actual download blocked')
    status, _, body = request(suffix='&pjh5pwp_view=import'); dom = Forms(); dom.feed(body.decode()); preview = dom.operation('import_preview')['values']
    payload['themes'][0]['id'] = 'uploaded'; payload['themes'][0]['label'] = '</td><script>bad()</script>'
    status, _, body = request(preview, json.dumps(payload).encode())
    check(status == 200 and b'Review before importing' in body, 'Real multipart PHP upload provenance accepted')
    dom = Forms(); dom.feed(body.decode()); check(dom.scripts == 0, 'Actual preview XSS escaped'); commit = dom.operation('import_commit')['values'] + [('confirm', '1')]
    status, _, body = request(commit, suffix='&session=other'); check(status == 200 and b'different login session' in body, 'Cross-session review denied over HTTP')
    status, _, body = request(commit, suffix='&stale=1'); check(status == 200 and b'library changed' in body, 'Stale signed context denied over HTTP')
    status, headers, _ = request(commit); check(status == 303 and 'pjh5pwp_imported=1' in headers['Location'], 'Actual confirmation follows POST/303/GET')
    check(headers.get('X-Fixture-Writes') == '1', 'Exactly one complete fixture write on confirmation')
    native = dict(payload['themes'][0], id='native_upload', _config_name='h5p_themer.theme.native_upload', schema_version=2, density='small')
    status, _, body = request(preview, json.dumps(native).encode()); check(b'Review before importing' in body, 'Actual native Backdrop upload preview')
    status, _, body = request(preview, b'<?php echo "execute"; ?>'); check(b'not supported theme JSON' in body and b'execute' not in body, 'Executable-looking input rejected as JSON, never executed')
    status, _, body = request(preview, b' ' * 1048577); check(b'exceeds the 1 MiB limit' in body, 'Actual oversize upload rejected')
    print(f'PASS transfer HTTP/DOM: {checks} checks; loopback-only disposable fixtures, no CMS.')
finally:
    proc.terminate()
    try: proc.wait(timeout=5)
    except subprocess.TimeoutExpired: proc.kill(); proc.wait(timeout=5)
