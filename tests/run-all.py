"""Consolidated local inventory. No live CMS bootstrap; DB tests require explicit inputs."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--php', required=True)
parser.add_argument('--h5p-root', required=True, type=Path)
parser.add_argument('--wp-source', required=True, type=Path)
parser.add_argument('--backdrop-root', type=Path)
parser.add_argument('--browser', required=True)
parser.add_argument('--report-dir', required=True, type=Path)
parser.add_argument('--database-credentials', type=Path, help='Explicit opt-in: literal server credentials, never the source DB_NAME')
parser.add_argument('--wordpress-fixture', type=Path, help='Fresh private copied WordPress core with current plugin installed; never a site')
args = parser.parse_args()
report = args.report_dir.resolve()
report.mkdir(parents=True, exist_ok=False)
jobs = [('release/tooling', [sys.executable, '-B', 'tests/release/test_tooling.py'])]
php_suites = [
    'run', 'domain/run', 'storage/run', 'admin/run', 'admin/coexistence',
    'profile/run', 'profile/preview', 'profile/suspension', 'profile/installation',
    'profile/activation', 'owned-storage/run', 'owned-storage/activation', 'roles/run', 'transfer/run', 'export/run', 'import/run',
    'admin-transfer/run', 'runtime/run', 'runtime/coexistence', 'runtime/admission',
    'runtime/attachment', 'runtime/configuration', 'runtime/div-admission',
    'runtime/readiness', 'runtime/plugin-state-verification', 'runtime/admin-viewer',
]
for suite in php_suites:
    command = [args.php, '-n', f'tests/{suite}.php']
    if suite in ['runtime/div-admission', 'runtime/admin-viewer']:
        command.append(str(args.h5p_root))
    jobs.append((suite, command))
jobs.append(('actual-h5p', [args.php, '-n', 'tests/run.php', str(args.h5p_root)]))
if args.backdrop_root:
    jobs.append(('backdrop-parity', [args.php, '-n', 'tests/domain/run.php', str(args.backdrop_root)]))
for suite in ['transport', 'qualification']:
    jobs.append((f'runtime/{suite}', [args.php, '-n', f'tests/runtime/{suite}.php', str(args.h5p_root)]))
css = args.h5p_root / 'h5p-php-library/styles/h5p-theme-variables.css'
jobs.append(('profile-preview-core-css', [args.php, '-n', 'tests/profile/preview.php', '--check', str(css)]))
for suite in ['admin/browser-behavior', 'profile/preview-behavior', 'runtime/theme-controller',
              'runtime/density-controller', 'runtime/lifecycle-controller',
              'runtime/readiness-controller', 'runtime/return-controller', 'runtime/slow-readiness-investigation', 'runtime/admin-viewer']:
    command = ['node', f'tests/{suite}.js']
    if suite == 'runtime/density-controller':
        command.append(str(args.h5p_root))
    label = 'runtime/admin-viewer-controller' if suite == 'runtime/admin-viewer' else suite
    jobs.append((label, command))
for suite in ['admin/headless-browser', 'profile/preview-browser']:
    jobs.append((suite, ['node', f'tests/{suite}.js', args.php, str(css), args.browser]))
jobs.extend([
    ('runtime/http-check', ['python', '-B', 'tests/runtime/http-check.py', args.php, str(args.wp_source), str(args.h5p_root)]),
    ('admin-transfer/http-dom', ['python', '-B', 'tests/admin-transfer/http-dom.py', args.php]),
])
skipped = []
if args.database_credentials:
    # Normal ini preserves mysqli in subprocesses used by storage/mysql.php.
    for suite in ['storage/mysql', 'profile/mysql', 'roles/mysql', 'import/mysql', 'profile/installation-mysql', 'owned-storage/mysql']:
        command = [args.php, f'tests/{suite}.php', str(args.wp_source), str(args.database_credentials)]
        if suite == 'import/mysql':
            command.append('--admin-ui')
        jobs.append((suite, command))
    jobs.append(('owned-storage/production-mysql', [args.php, 'tests/owned-storage/mysql.php', str(args.wp_source), str(args.database_credentials), '--production']))
    if args.wordpress_fixture:
        jobs.append(('profile/activation-wordpress', [args.php, 'tests/profile/activation-wordpress.php',
                    str(args.wordpress_fixture), str(args.database_credentials)]))
        jobs.append(('owned-storage/wordpress-innodb', [args.php, 'tests/profile/activation-wordpress.php',
                    str(args.wordpress_fixture), str(args.database_credentials), 'InnoDB']))
    else:
        skipped.append('profile/activation-wordpress: private WordPress fixture not supplied')
else:
    skipped.append('Nine disposable database/lifecycle suites: explicit database inputs not supplied')
results = []
for name, command in jobs:
    try:
        run = subprocess.run(command, cwd=ROOT, capture_output=True, text=True, timeout=180,
                             creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0),
                             env=dict(os.environ, PYTHONDONTWRITEBYTECODE='1'))
        output = run.stdout + run.stderr
        code = run.returncode
    except subprocess.TimeoutExpired:
        output, code = 'Timed out after 180 seconds; inspect fixture processes before retrying.', -1
    log = name.replace('/', '-') + '.log'
    (report / log).write_text(output, encoding='utf-8')
    results.append({'suite': name, 'exit_code': code, 'log': log})
    print(('PASS ' if code == 0 else 'FAIL ') + name, flush=True)
(report / 'results.json').write_text(json.dumps({'php': args.php, 'results': results, 'skipped': skipped}, indent=2), encoding='utf-8')
print(f'{sum(r["exit_code"] == 0 for r in results)}/{len(results)} commands passed; {len(skipped)} inventory exclusions.', flush=True)
raise SystemExit(1 if any(r['exit_code'] != 0 for r in results) else 0)
