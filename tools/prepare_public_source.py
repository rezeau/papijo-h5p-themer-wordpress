"""Project the complete reviewed development repository; never reads private evidence."""
import argparse
from pathlib import Path
import re
import sys
from urllib.parse import unquote

from release_common import (PRODUCTION_DIRS, PolicyError, canonical, denied, digest,
                            inventory, plain_path, policy_paths, read_file, require)
from package_release import audit as audit_zip

POLICY = 'docs/public-source-allowlist.txt'
LOCAL_PATH = re.compile(r'(?<![A-Za-z0-9])(?:[A-Za-z]:[/\\]|/(?:home|Users|var/www)/)[^\s`<>"\x27|)\]]+')
EVIDENCE_PATH = re.compile(r'\.local-acceptance[/\\](?![<*])[^\s`<>"\x27|)\],;]+')
FINGERPRINT = re.compile(r'\b(?:[0-9A-Fa-f]{64}|[0-9A-F]{40}|(?:[0-9A-Fa-f]{2}:){19,31}[0-9A-Fa-f]{2})\b')
LINK = re.compile(r'(?<!!)\[([^\]]*)\]\(([^)]+)\)')
SECRET = re.compile(r'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY|\bgh[pousr]_[A-Za-z0-9]{20,}|\bgithub_pat_[A-Za-z0-9_]{20,}|\bAKIA[0-9A-Z]{16}')
DB_PASSWORD_LITERAL = re.compile(r'''define\(\s*['"]DB_PASSWORD['"]\s*,\s*['"][^'"\s]+['"]''', re.I)
PROFILE = re.compile(r'\b[a-z0-9]{8}\.default(?:-release)?\b')


def public_local_path(match):
    path = match[0].replace('\\', '/')
    for marker, replacement in [('/papijo-h5p-themer-wordpress', 'PROJECT_ROOT'),
                                ('/h5p-themer-v050-integration', 'BACKDROP_REFERENCE_ROOT'),
                                ('/wp-test', 'WORDPRESS_ROOT')]:
        if marker in path:
            return replacement + path.split(marker, 1)[1]
    if re.search(r'/php[^/]*/php\.exe$', path, re.I):
        return 'PHP_CLI'
    return 'LOCAL_PATH_OMITTED'


def project_document(data):
    text = data.decode('utf-8-sig')
    original = text
    # Remove private link destinations before transforming prose/code references.
    text = LINK.sub(lambda m: m[1] + ' (private reference omitted)'
                    if '.local-acceptance' in m[2] or LOCAL_PATH.search(m[2]) else m[0], text)
    text = LOCAL_PATH.sub(public_local_path, text)
    text = EVIDENCE_PATH.sub('PRIVATE_EVIDENCE_OMITTED', text)
    text = FINGERPRINT.sub('PRIVATE_VERIFICATION_DIGEST_OMITTED', text)
    text = PROFILE.sub('BROWSER_PROFILE_OMITTED', text)
    text = re.sub(r'\bwp-test\b', 'acceptance.example.test', text)
    if text != original:
        banner = ('\n\n> Public documentation projection: machine paths, private evidence references, '
                  'local host/profile identifiers and verification fingerprints are replaced with '
                  'placeholders. Technical findings and original checkpoint claims are retained; '
                  'historical operator commands are records, not current deployment instructions. '
                  'The private development original is preserved. Use CONTRIBUTING.md for current commands.')
        split = text.find('\n\n')
        text = text[:split] + banner + text[split:] if split >= 0 else text + banner + '\n'
    return text.encode('utf-8'), text != original


def audit_public_bytes(name, data):
    require(not denied(name), 'Private/development output cannot be published: ' + name)
    try:
        text = data.decode('utf-8-sig')
    except UnicodeError as error:
        raise PolicyError('Unexpected binary in reviewed public source: ' + name) from error
    require(not SECRET.search(text) and not DB_PASSWORD_LITERAL.search(text), 'Credential/private-key-shaped value detected: ' + name)
    for match in LOCAL_PATH.finditer(text):
        # Two reusable browser fixtures have a generic Windows install default,
        # overridable by CLI. It contains no account/site path or private state.
        generic_edge = (name in {'tests/admin/headless-browser.js', 'tests/profile/preview-browser.js'}
                        and match[0].replace('\\', '/').startswith('C:' + '//Program'))
        require(generic_edge, 'Unprojected local filesystem path: ' + name)
    if name.startswith('docs/') and name.endswith('.md'):
        require(not EVIDENCE_PATH.search(text), 'Private evidence reference remains: ' + name)


def validate_links(files):
    for name, data in files.items():
        if not name.endswith('.md'):
            continue
        text = data.decode('utf-8-sig')
        for _label, target in LINK.findall(text):
            target = target.strip('<>')
            if re.match(r'^[A-Za-z][A-Za-z0-9+.-]*:', target) or target.startswith('#'):
                continue
            # Resolve the link lexically against the in-memory projection, never the filesystem.
            parts = name.split('/')[:-1]
            for part in unquote(target.split('#')[0]).split('/'):
                if part == '..':
                    require(bool(parts), 'Documentation link escapes repository: ' + name)
                    parts.pop()
                elif part not in ('', '.'):
                    parts.append(part)
            require('/'.join(parts) in files, 'Broken public documentation link: ' + name + ' -> ' + target)


def prepare(root):
    paths = policy_paths(root, POLICY)
    actual = {p for p in inventory(root) if not denied(p)}
    require(actual == set(paths), 'Public source policy has missing/unreviewed files')
    require({'README.md', 'CONTRIBUTING.md', '.gitignore', '.gitattributes', POLICY,
             'tools/package_release.py', 'tools/prepare_public_source.py', 'tests/run-all.py',
             'tests/release/test_tooling.py', 'docs/architecture.md', 'docs/testing.md'} <= set(paths),
            'Complete development repository requirements missing')
    zip_files, _manifest = audit_zip(root)
    require(set(zip_files) <= set(paths), 'Public repository must include all production source')
    projected, changed = {}, []
    for name in paths:
        original = read_file(root, name)
        data, modified = project_document(original) if name.startswith('docs/') and name.endswith('.md') else (original, False)
        audit_public_bytes(name, data)
        projected[name] = data
        if modified:
            changed.append(name)
    require(all(projected[name] == data for name, data in zip_files.items()), 'Projection modified production/distribution bytes')
    validate_links(projected)
    report = {'format': 1, 'files': [{'path': p, 'bytes': len(b), 'sha256': digest(b)} for p, b in sorted(projected.items())],
              'projected_documents': changed, 'production_files_unchanged': len(zip_files),
              'local_links': 'valid', 'private_tree_read': False}
    report['projection_sha256'] = digest(canonical(report))
    return projected, report


def write_projection(files, destination, source_root):
    destination = Path(destination).absolute()
    plain_path(destination.parent)
    require(not destination.exists() and not destination.is_symlink(), 'Fresh public projection directory required')
    source = plain_path(source_root)
    if destination.is_relative_to(source):
        require(destination.relative_to(source).parts[0] in {'build', '.local-acceptance'}, 'Projection output must be isolated from source')
    destination.mkdir()
    for name, data in sorted(files.items()):
        target = destination.joinpath(*name.split('/'))
        target.parent.mkdir(parents=True, exist_ok=True)
        plain_path(target.parent)
        with target.open('xb') as stream:
            stream.write(data)
    require(inventory(destination) == sorted(files), 'Projected file set changed')
    require(all(read_file(destination, p) == b for p, b in files.items()), 'Projected file verification failed')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', type=Path, default=Path(__file__).resolve().parents[1])
    parser.add_argument('--output', type=Path, help='Fresh development-source projection directory; never a ZIP')
    args = parser.parse_args()
    try:
        files, report = prepare(args.root)
        if args.output:
            write_projection(files, args.output, args.root)
        print(canonical(report).decode(), end='')
    except (PolicyError, OSError, ValueError) as error:
        print('Public source validation failed: ' + str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
