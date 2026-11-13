"""Offline deterministic ZIP tooling. Audit is read-only; build is an explicit future operation."""
import argparse
from io import BytesIO
import json
from pathlib import Path
import platform
import stat
import sys
import zipfile
import zlib

from release_common import (ENTRY, PRODUCTION_DIRS, SLUG, PolicyError, canonical, digest,
                            input_manifest, inventory, plain_path, policy_paths, read_file,
                            relative_name, require, validate_payload, write_new)

POLICY = 'docs/release-zip-allowlist.txt'
STAMP = (1980, 1, 1, 0, 0, 0)
MODE = stat.S_IFREG | 0o644


def audit(root, expected_version=None):
    paths = policy_paths(root, POLICY, zip_policy=True)
    existing = inventory(root)
    production = {p for p in existing if p.split('/')[0] in PRODUCTION_DIRS or ('/' not in p and p.endswith('.php'))}
    require(production == {p for p in paths if p.split('/')[0] in PRODUCTION_DIRS or p == ENTRY},
            'Unexpected or missing production file; ZIP policy review required')
    files = {name: read_file(root, name) for name in paths}
    metadata = validate_payload(files, expected_version)
    return files, input_manifest(files, metadata)


def archive_bytes(files):
    """Use synthetic files in E1 tests; do not call on the real source until build approval."""
    stream = BytesIO()
    with zipfile.ZipFile(stream, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in sorted(files.items()):
            info = zipfile.ZipInfo(SLUG + '/' + relative_name(name), date_time=STAMP)
            info.create_system = 3
            info.create_version = 20
            info.extract_version = 20
            info.external_attr = MODE << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            info.comment = info.extra = b''
            archive.writestr(info, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    return stream.getvalue()


def verify_archive(data, files, manifest):
    require(input_manifest(files, validate_payload(files, manifest['version'])) == manifest,
            'Manifest or source bytes changed')
    try:
        with zipfile.ZipFile(BytesIO(data)) as archive:
            infos = archive.infolist()
            expected = [SLUG + '/' + p for p in sorted(files)]
            names = [info.filename for info in infos]
            require(names == expected and len(names) == len(set(n.casefold() for n in names)),
                    'Archive member set, ordering, prefix or duplicates invalid')
            require(archive.comment == b'', 'Unexpected archive comment')
            unpacked = {}
            for info in infos:
                relative_name(info.filename)
                require(not info.is_dir() and not info.flag_bits & 1, 'Directory/encrypted member refused')
                require(info.date_time == STAMP and info.create_system == 3
                        and info.create_version == 20 and info.extract_version == 20
                        and info.external_attr == MODE << 16 and info.internal_attr == 0
                        and info.compress_type == zipfile.ZIP_DEFLATED and not info.extra and not info.comment,
                        'Nonreproducible or unsafe archive metadata')
                name = info.filename[len(SLUG) + 1:]
                require(info.file_size == len(files[name]), 'Archive member size mismatch')
                body = archive.read(info)
                require(body == files[name], 'Archive member hash/bytes mismatch')
                unpacked[name] = body
            require(archive.testzip() is None, 'Archive CRC failure')
            require(validate_payload(unpacked, manifest['version']) == validate_payload(files), 'Archived release metadata changed')
        # Reject prepended/trailing data or alternative encodings of ZIP headers/metadata.
        # Pin Python/zlib for byte identity; source hashes remain portable across toolchains.
        require(data == archive_bytes(files), 'Archive has noncanonical bytes or trailing data')
    except (zipfile.BadZipFile, RuntimeError, KeyError, UnicodeError, zlib.error) as error:
        raise PolicyError('Invalid/corrupt release archive') from error
    return {'sha256': digest(data), 'bytes': len(data), 'members': len(files), 'inputs_sha256': manifest['inputs_sha256']}


def build(root, expected_version, approved_manifest, output_dir):
    files, manifest = audit(root, expected_version)
    require(manifest == approved_manifest, 'Approved input manifest is stale or tampered')
    first, second = archive_bytes(files), archive_bytes(files)
    require(first == second, 'Repeated archive build is not reproducible')
    verification = verify_archive(first, files, manifest)
    # Re-read inputs immediately before writing: no unnoticed source change during build.
    require(audit(root, expected_version)[1] == manifest, 'Source changed during build')
    output = Path(output_dir).absolute()
    plain_path(output.parent)
    require(not output.exists() and not output.is_symlink(), 'Fresh output directory required')
    source = plain_path(root)
    if output.is_relative_to(source):
        require(output.relative_to(source).parts[0] in {'build', '.local-acceptance'}, 'Build output must be isolated from source')
    output.mkdir()
    filename = SLUG + '-' + manifest['version'] + '.zip'
    write_new(output / filename, first)
    write_new(output / (filename + '.sha256'), (verification['sha256'] + '  ' + filename + '\n').encode())
    write_new(output / 'inputs.json', canonical(manifest))
    write_new(output / 'verification.json', canonical(dict(verification,
              python=platform.python_version(), zlib=zlib.ZLIB_RUNTIME_VERSION, version=manifest['version'])))
    return verification


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', type=Path, default=Path(__file__).resolve().parents[1])
    sub = parser.add_subparsers(dest='command', required=True)
    check = sub.add_parser('audit', help='Validate source and print a SHA-256 manifest; creates no ZIP')
    check.add_argument('--expect-version', required=True)
    check.add_argument('--manifest-out', type=Path)
    create = sub.add_parser('build', help='Explicit release-artifact creation; requires separate maintainer approval')
    create.add_argument('--expect-version', required=True)
    create.add_argument('--approved-manifest', required=True, type=Path)
    create.add_argument('--output-dir', required=True, type=Path)
    verify = sub.add_parser('verify', help='Verify an existing artifact against reviewed source/manifest')
    verify.add_argument('--expect-version', required=True)
    verify.add_argument('--approved-manifest', required=True, type=Path)
    verify.add_argument('--archive', required=True, type=Path)
    args = parser.parse_args()
    try:
        if args.command == 'audit':
            result = audit(args.root, args.expect_version)[1]
            if args.manifest_out:
                write_new(args.manifest_out, canonical(result))
        else:
            reviewed = json.loads(plain_path(args.approved_manifest).read_text(encoding='utf-8'))
            if args.command == 'build':
                result = build(args.root, args.expect_version, reviewed, args.output_dir)
            else:
                files, manifest = audit(args.root, args.expect_version)
                require(manifest == reviewed, 'Approved manifest does not match source')
                result = verify_archive(plain_path(args.archive).read_bytes(), files, manifest)
        print(canonical(result).decode(), end='')
    except (PolicyError, OSError, ValueError, KeyError) as error:
        print('Release validation failed: ' + str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
