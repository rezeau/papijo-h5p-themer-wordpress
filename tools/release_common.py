"""Offline policy primitives shared by ZIP and complete public-source preparation."""
from pathlib import Path, PurePosixPath
import hashlib
import json
import os
import re
import stat

SLUG = 'papijo-h5p-themer-wordpress'
ENTRY = SLUG + '.php'
ZIP_POLICY_DIGEST = 'b992b630fda98783b86504394fcd3a0eb5a5911c7690e524fd3a2844aefa77ae'
PRODUCTION_DIRS = {'includes', 'assets', 'presets', 'languages', 'licenses'}
EXCLUDED_ROOTS = {'.local-acceptance', '.git', 'build', 'dist', 'release', 'releases',
                  'node_modules', 'vendor', '.idea', '.vscode'}
LICENSE_DIGESTS = {
    'LICENSE': '4052be61f35ad6a156dcee919b8f99058b24b0417a152ecef746f702c7f34c5c',
    'licenses/GPL-2.0.txt': '8177f97513213526df2cf6184d8ff986c675afb514d4e68a404010521b880643',
    'licenses/MIT-H5P-THEME-PICKER.txt': '6934d3015453129c55f5da0d4c65aabdd7373394b7d02697d96dd7557f1c3489',
}


class PolicyError(ValueError):
    """Fail closed with a path/code, without dumping source/configuration values."""


def require(condition, message):
    if not condition:
        raise PolicyError(message)


def digest(data):
    return hashlib.sha256(data).hexdigest()


def canonical(value):
    return (json.dumps(value, sort_keys=True, indent=2, ensure_ascii=True) + '\n').encode()


def relative_name(name):
    require(isinstance(name, str) and name and name.isascii(), 'Invalid relative path')
    require(not any(ord(c) < 32 or ord(c) == 127 for c in name), 'Control character in path')
    require('\\' not in name and ':' not in name and not name.startswith('/'), 'Unsafe path syntax')
    parts = name.split('/')
    require(all(p not in ('', '.', '..') and p == p.rstrip(' .') for p in parts), 'Unsafe path component')
    require(all(not re.fullmatch(r'(?i)(con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\..*)?', p) for p in parts), 'Reserved device path')
    require(str(PurePosixPath(name)) == name, 'Noncanonical path')
    return name


def denied(name):
    parts = name.split('/')
    if (parts[0].casefold() in EXCLUDED_ROOTS
            or any(p.casefold() in {'.local-acceptance', '.git', '__pycache__', 'node_modules', 'vendor'} for p in parts)):
        return True
    leaf = parts[-1].casefold()
    return (leaf in {'wp-config.php', '.ds_store', 'thumbs.db', '.env'}
            or leaf.startswith('.env.') or '.private.' in leaf
            or leaf.endswith(('.sql', '.sql.gz', '.zip', '.tar', '.tar.gz', '.7z', '.pem', '.key',
                              '.p12', '.pfx', '.log', '.tmp', '.temp', '.bak', '.before', '.original',
                              '.pyc', '.pyo', '~')))


def plain_path(path):
    """Check every lexical component before resolve/read; rejects Windows reparse points."""
    path = Path(os.path.abspath(path))
    for part in reversed((path, *path.parents)):
        info = part.lstat()
        require(not stat.S_ISLNK(info.st_mode)
                and not (getattr(info, 'st_file_attributes', 0) & stat.FILE_ATTRIBUTE_REPARSE_POINT),
                'Symlink/junction/reparse point refused')
    return path


def read_file(root, name):
    relative_name(name)
    root = plain_path(root)
    target = plain_path(root.joinpath(*name.split('/')))
    require(target.is_relative_to(root), 'Source path escaped root')
    require(stat.S_ISREG(target.stat().st_mode), 'Regular source file required: ' + name)
    return target.read_bytes()


def inventory(root, excluded=EXCLUDED_ROOTS):
    root = plain_path(root)
    found = []

    def walk(folder):
        for child in sorted(folder.iterdir()):
            name = child.relative_to(root).as_posix()
            # Excluded trees are opaque. In particular, never walk private evidence.
            if name.split('/')[0].casefold() in excluded or child.name == '__pycache__':
                continue
            plain_path(child)
            if child.is_dir():
                walk(child)
            else:
                require(stat.S_ISREG(child.stat().st_mode), 'Nonregular source object')
                found.append(relative_name(name))
    walk(root)
    require(len(found) == len({p.casefold() for p in found}), 'Case-colliding source paths')
    return sorted(found)


def policy_paths(root, policy, zip_policy=False):
    data = read_file(root, policy)
    try:
        text = data.decode('utf-8')
    except UnicodeError as error:
        raise PolicyError('Policy must be UTF-8') from error
    paths = text.splitlines()
    require(paths and all(paths), 'Empty policy or blank policy entry')
    for name in paths:
        relative_name(name)
        require(not denied(name), 'Forbidden policy entry: ' + name)
    require(paths == sorted(paths), 'Policy ordering is not deterministic')
    require(len(paths) == len({p.casefold() for p in paths}), 'Duplicate/case-colliding policy entries')
    if zip_policy:
        require(len(paths) == 93 and digest(('\n'.join(paths) + '\n').encode()) == ZIP_POLICY_DIGEST,
                'Approved 93-file ZIP policy changed; deliberate policy review required')
    return paths


def valid_version(value):
    if not re.fullmatch(r'(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?', value):
        return False
    return '-' not in value or all(not part.isdigit() or len(part) == 1 or part[0] != '0'
                                   for part in value.split('-', 1)[1].split('.'))


def validate_payload(files, expected_version=None):
    """Check declarations/resources on source bytes and again on archived bytes."""
    require(ENTRY in files, 'Required WordPress plugin entry point missing')
    require(all(files.values()), 'Empty required payload file')
    try:
        entry = files[ENTRY].decode('utf-8-sig')
        readme = files['readme.txt'].decode('utf-8-sig')
        notices = files['THIRD-PARTY-NOTICES.md'].decode('utf-8-sig')
    except (KeyError, UnicodeError) as error:
        raise PolicyError('Required distribution documentation missing/invalid') from error
    require(entry.startswith('<?php'), 'PHP entry point opening tag missing')
    block = re.match(r'<\?php\s*/\*\*(.*?)\*/', entry, re.S)
    require(block is not None, 'WordPress plugin header missing')
    headers = {}
    for key, value in re.findall(r'^\s*\*\s*([^:\r\n]+):\s*([^\r\n]+)', block[1], re.M):
        require(key not in headers, 'Duplicate plugin header field')
        headers[key] = value.strip()
    required = {'Plugin Name': 'Papi Jo H5P Themer for WordPress', 'Requires at least': '6.5',
                'Requires PHP': '7.4', 'Text Domain': SLUG, 'Domain Path': '/languages',
                'License': 'GPL-3.0-or-later', 'License URI': 'https://www.gnu.org/licenses/gpl-3.0.html'}
    require(all(headers.get(k) == v for k, v in required.items()), 'Plugin header/compatibility/license declaration mismatch')
    version = headers.get('Version', '')
    require(valid_version(version), 'Invalid release version')
    constants = re.findall(r"define\(\s*'PJH5PWP_VERSION'\s*,\s*'([^']+)'\s*\)", entry)
    require(constants == [version], 'Header/runtime version mismatch')
    require('register_activation_hook(' in entry, 'Profile activation registration missing')
    if expected_version is not None:
        require(version == expected_version, 'Requested version does not match source; source will not be modified')
    for field, expected in [('Stable tag', version), ('Requires at least', '6.5'),
                            ('Requires PHP', '7.4'), ('License', 'GPL-3.0-or-later')]:
        require(re.findall(r'^' + re.escape(field) + r':\s*(\S+)\s*$', readme, re.M) == [expected],
                'Distribution readme declaration mismatch: ' + field)
    changelog = readme.split('== Changelog ==')
    require(len(changelog) == 2, 'Exactly one distribution changelog required')
    newest = re.search(r'^= ([^=\r\n]+) =\s*$', changelog[1], re.M)
    require(newest is not None and newest[1] == version, 'Newest changelog/release version mismatch')
    for name, expected in LICENSE_DIGESTS.items():
        require(name in files and digest(files[name]) == expected, 'Pinned full license resource changed/missing: ' + name)
    for text in ['GPL-3.0-or-later', 'GPL-2.0-or-later', 'MIT', 'Oliver Tacke', 'Matheus Mathias',
                 'Daylight', 'Lavender', 'Mint', 'Sunset', 'Dark',
                 'd9b53d706a94dac322b89885bf668145ab3803c4', '478fed30d2a4af58638f2ddbea3603db1d4d8c5f']:
        require(text in notices, 'Required attribution/provenance missing')
    return {'version': version, 'requires_wordpress': headers['Requires at least'], 'requires_php': headers['Requires PHP']}


def input_manifest(files, metadata):
    body = dict(format=1, slug=SLUG, **metadata,
                files=[{'path': name, 'bytes': len(data), 'sha256': digest(data)} for name, data in sorted(files.items())])
    return dict(body, inputs_sha256=digest(canonical(body)))


def write_new(path, data):
    path = Path(os.path.abspath(path))
    plain_path(path.parent)
    require(not path.exists() and not path.is_symlink(), 'Output already exists')
    with path.open('xb') as stream:
        stream.write(data)
