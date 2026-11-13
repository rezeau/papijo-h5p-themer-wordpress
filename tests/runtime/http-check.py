"""Disposable loopback HTTP/auth/header verification against installed WP definitions.

Usage: python tests/runtime/http-check.py <php.exe> <WordPress source root>
No WordPress bootstrap, configuration, real cookies or database access.
"""
import http.cookiejar
import json
import os
from pathlib import Path
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

php, wp_source = sys.argv[1:3]
h5p_source = sys.argv[3] if len(sys.argv) > 3 else None
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
origin = f'http://127.0.0.1:{port}'
env = dict(os.environ, PJH5PWP_TEST_WP_SOURCE=wp_source)
if h5p_source:
    env['PJH5PWP_TEST_H5P_SOURCE'] = h5p_source
checks = 0

def verify(value, message):
    global checks
    if not value:
        raise AssertionError(message)
    checks += 1

def read_json(response):
    raw = response.read()
    try:
        return json.loads(raw)
    except ValueError:
        raise RuntimeError(raw.decode(errors='replace'))

with tempfile.TemporaryFile() as log:
    process = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', str(Path(__file__).with_name('http-fixture.php').resolve())],
                               env=env, stdout=log, stderr=log, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    try:
        for attempt in range(100):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                    break
            except OSError:
                time.sleep(0.05)
        else:
            raise RuntimeError('Fixture server unavailable')
        clients = []
        for user in (0, 1, 2, 3):
            jar = http.cookiejar.CookieJar()
            client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
            if user:
                with client.open(origin + f'/login/{user}') as response:
                    verify(read_json(response) == {'fixture': True}, 'Disposable login fixture')
            clients.append((client, jar))

        def request(client, method='POST', headers=None, extra=None):
            fields = dict(action='pjh5pwp_appearance', request_id='a' * 32)
            fields.update(extra or {})
            hdr = {'Origin': origin, 'Referer': origin + '/lesson/', 'X-PJH5PWP-Request': 'appearance-v1',
                   'Sec-Fetch-Site': 'same-origin', 'Sec-Fetch-Dest': 'empty', 'Sec-Fetch-Mode': 'cors'}
            hdr.update(headers or {})
            # OPTIONS includes action in URL so the early route guard sees the same route.
            url = origin + '/wp-admin/admin-ajax.php' + ('?action=pjh5pwp_appearance' if method != 'POST' else '')
            req = urllib.request.Request(url, data=urllib.parse.urlencode(fields).encode() if method == 'POST' else None, headers=hdr, method=method)
            try:
                response = client.open(req)
            except urllib.error.HTTPError as error:
                response = error
            with response:
                payload = read_json(response)
                verify('no-store' in response.headers.get('Cache-Control', '') and 'private' in response.headers.get('Cache-Control', ''), 'Actual private/no-store HTTP header')
                for header in ('Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials', 'ETag', 'Last-Modified', 'Set-Cookie'):
                    verify(response.headers.get(header) is None, 'No disclosure/conditional-cache header: ' + header)
                verify(response.headers.get('CDN-Cache-Control') == 'no-store', 'Actual CDN no-store header')
                verify(response.headers.get('Surrogate-Control') == 'no-store', 'Actual proxy no-store header')
                verify(response.headers.get('Cross-Origin-Resource-Policy') == 'same-origin', 'Actual CORP header')
                verify(response.headers.get('Content-Type') == 'application/json; charset=UTF-8', 'JSON response')
                return response.status, payload

        for index, expected in ((0, None), (1, 'lavender'), (2, 'mint'), (3, None), (2, 'mint'), (0, None)):
            status, payload = request(clients[index][0])
            verify(status == 200, 'Successful authenticated/anonymous HTTP dispatch')
            verify(payload['appearance']['theme']['id'] == expected, 'No cross-user cache reuse')
        clients[2][1].clear()
        verify(request(clients[2][0])[1]['appearance']['theme']['id'] is None, 'Logout ignores prior descriptor')
        # A forged username in a genuine WordPress cookie must fail the real HMAC check.
        for cookie in clients[1][1]:
            cookie.value = cookie.value.replace('fixture1', 'fixture2')
        verify(request(clients[1][0])[1]['appearance']['theme']['id'] is None, 'Forged auth cookie becomes anonymous')
        for mode in ('revoked', 'expired'):
            with clients[1][0].open(origin + '/login/1/' + mode) as response:
                verify(read_json(response) == {'fixture': True}, 'Disposable invalid-session login fixture')
            verify(request(clients[1][0])[1]['appearance']['theme']['id'] is None, 'Signed ' + mode + ' cookie becomes anonymous')
        for method in ('OPTIONS', 'GET'):
            status, payload = request(clients[0][0], method=method)
            verify(status == 405 and payload['appearance']['theme'] is None, 'Preflight/navigation rejected before WP CORS early exit')
        for bad in ({'Origin': 'https://evil.invalid'}, {'Origin': 'null'}, {'X-PJH5PWP-Request': ''}, {'Referer': origin + '/wp-admin/admin.php'}):
            status, payload = request(clients[0][0], headers=bad)
            verify(status == 403 and payload['appearance']['density'] is None, 'Cross-origin/admin rejection')
        status, payload = request(clients[0][0], extra={'user_id': '1'})
        verify(status == 400 and payload['appearance']['theme'] is None, 'Forged identity rejected over HTTP')
        if h5p_source:
            # Native WordPress session-bound nonces, with only content ownership backed by a double.
            for user, expected in ((1, 'lavender'), (2, 'mint')):
                client = clients[user][0]
                with client.open(origin + f'/login/{user}') as response:
                    verify(read_json(response) == {'fixture': True}, 'Fresh authenticated fixture session')
                with client.open(origin + '/admin-grant/10') as response:
                    nonce = read_json(response)['nonce']
                fields = {'admin_content_id': '10', 'admin_nonce': nonce, 'admin_players': '10'}
                ref = {'Referer': origin + '/wp-admin/admin.php?page=h5p&task=show&id=10'}
                status, payload = request(client, headers=ref, extra=fields)
                verify(status == 200 and payload['appearance']['theme']['id'] == expected, 'Native cookie/nonce admin appearance')
                status, _ = request(client, headers=ref, extra=dict(fields, admin_nonce='0000000000'))
                verify(status == 403, 'Forged native admin nonce rejected')
                status, _ = request(client, headers=ref, extra=dict(fields, admin_players='10,11'))
                verify(status == (200 if user == 1 else 403), 'Fresh own/other-player authorization over HTTP')
                status, _ = request(clients[0][0], headers=ref, extra=fields)
                verify(status == 403, 'Guest cannot reuse native authenticated grant')
                other = clients[2 if user == 1 else 1][0]
                status, _ = request(other, headers=ref, extra=fields)
                verify(status == 403, 'Native nonce is bound to the current session/user')
                with client.open(origin + f'/login/{user}/revoked') as response:
                    read_json(response)
                status, _ = request(client, headers=ref, extra=fields)
                verify(status == 403, 'Revoked native session cannot retain an admin grant')
    except Exception:
        log.seek(0)
        sys.stderr.write(log.read().decode(errors='replace'))
        raise
    finally:
        process.terminate()
        process.wait(timeout=10)
print(f'Phase 2B-2B HTTP: {checks} assertions passed; native WordPress cookie/session validation and CORS; loopback fixtures only.')
