"""Adversarial offline release tests. ZIPs are synthetic BytesIO buffers, never disk artifacts."""
from io import BytesIO
import json
import os
from pathlib import Path
import stat
import subprocess
import sys
import tempfile
from types import SimpleNamespace
import unittest
from unittest.mock import patch
import warnings
import zipfile

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools'))
import release_common as common
import package_release as packaging
import prepare_public_source as public

HEADER = """<?php
/**
 * Plugin Name: Papi Jo H5P Themer for WordPress
 * Description: Synthetic test fixture, not a release.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: papijo-h5p-themer-wordpress
 * Domain Path: /languages
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */
define( 'PJH5PWP_VERSION', '0.1.0' );
register_activation_hook('synthetic-only', 'not_executable_fixture');
"""


def synthetic_files():
    paths = (ROOT / packaging.POLICY).read_text(encoding='utf-8').splitlines()
    files = {p: b'Synthetic resource; no production implementation.\n' for p in paths}
    for p in common.LICENSE_DIGESTS:
        files[p] = (ROOT / p).read_bytes()
    files['THIRD-PARTY-NOTICES.md'] = (ROOT / 'THIRD-PARTY-NOTICES.md').read_bytes()
    files[common.ENTRY] = HEADER.encode()
    files['readme.txt'] = b'''=== Synthetic fixture ===
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-3.0-or-later
== Changelog ==
= 0.1.0 =
* Synthetic test only.
'''
    return files


class ReleaseTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='pjh5pwp-tooling-')
        def cleanup_created_fixture():
            target = Path(self.temp.name).resolve()
            self.assertEqual(Path(tempfile.gettempdir()).resolve(), target.parent)
            self.assertTrue(target.name.startswith('pjh5pwp-tooling-'))
            common.plain_path(target)
            self.temp.cleanup()
        self.addCleanup(cleanup_created_fixture)
        self.root = Path(self.temp.name) / 'source'
        self.root.mkdir()
        self.files = synthetic_files()
        for name, data in self.files.items():
            target = self.root / name
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(data)
        (self.root / 'docs').mkdir()
        (self.root / packaging.POLICY).write_bytes((ROOT / packaging.POLICY).read_bytes())

    def manifest(self):
        return common.input_manifest(self.files, common.validate_payload(self.files))

    def modified_archive(self, transform=None, extra=None):
        stream = BytesIO()
        with zipfile.ZipFile(stream, 'w') as archive:
            for name, data in sorted(self.files.items()):
                info = zipfile.ZipInfo(common.SLUG + '/' + name, packaging.STAMP)
                info.create_system = 3
                info.create_version = info.extract_version = 20
                info.external_attr = packaging.MODE << 16
                info.compress_type = zipfile.ZIP_DEFLATED
                if transform:
                    info, data = transform(info, data)
                archive.writestr(info, data, compresslevel=9)
            if extra:
                with warnings.catch_warnings():
                    warnings.simplefilter('ignore', UserWarning)
                    archive.writestr(*extra)
        return stream.getvalue()

    def test_real_source_audit_does_not_create_archive(self):
        # Real source is only audited, never passed to archive_bytes/build.
        files, manifest = packaging.audit(ROOT, '0.2.1')
        self.assertEqual(93, len(files))
        self.assertEqual('0.2.1', manifest['version'])
        self.assertEqual(['6.5', '7.4'], [manifest['requires_wordpress'], manifest['requires_php']])

    def test_candidate_metadata_and_requested_acknowledgments(self):
        candidate = json.loads((ROOT / 'docs/release-candidate-0.2.0.json').read_text(encoding='utf-8'))
        _files, current_manifest = packaging.audit(ROOT, '0.2.1')
        manifest = json.loads((ROOT / 'docs/release-g4-inputs.json').read_text(encoding='utf-8'))
        self.assertEqual(manifest['version'], candidate['version'])
        self.assertEqual('v' + manifest['version'], candidate['proposed_tag'])
        self.assertEqual(common.SLUG + '-' + manifest['version'] + '.zip', candidate['installer_filename'])
        self.assertEqual(93, candidate['installer_files'])
        self.assertRegex(candidate['installer_sha256'], r'^[0-9a-f]{64}$')
        # Frozen E4/beta.1 checks remain mandatory alongside the current candidate.
        frozen = json.loads((ROOT / 'docs/release-e4-inputs.json').read_text(encoding='utf-8'))
        body = {k: v for k, v in frozen.items() if k != 'inputs_sha256'}
        self.assertEqual(common.digest(common.canonical(body)), frozen['inputs_sha256'])
        previous = json.loads((ROOT / 'docs/release-candidate-beta1.json').read_text(encoding='utf-8'))
        self.assertEqual('0.1.1-beta.1', previous['version'])
        self.assertEqual('v0.1.1-beta.1', previous['proposed_tag'])
        self.assertEqual(common.SLUG + '-0.1.1-beta.1.zip', previous['installer_filename'])
        self.assertEqual(84, previous['installer_files'])
        self.assertEqual('69e45c7ad8f82f839d597cb03a6c7b2cc793b61b2403118a7e3e017f8f2fe703', previous['installer_sha256'])
        self.assertEqual(frozen['inputs_sha256'], previous['source_inputs_sha256'])
        # F3/beta.2 remains independently frozen, including its failed gate.
        beta2 = json.loads((ROOT / 'docs/release-candidate-beta2.json').read_text(encoding='utf-8'))
        f3_inputs = json.loads((ROOT / 'docs/release-f3-inputs.json').read_text(encoding='utf-8'))
        f3_body = {k: v for k, v in f3_inputs.items() if k != 'inputs_sha256'}
        self.assertEqual(common.digest(common.canonical(f3_body)), f3_inputs['inputs_sha256'])
        self.assertEqual('0.1.1-beta.2', beta2['version'])
        self.assertEqual('v0.1.1-beta.2', beta2['proposed_tag'])
        self.assertEqual(common.SLUG + '-0.1.1-beta.2.zip', beta2['installer_filename'])
        self.assertEqual(93, beta2['installer_files'])
        self.assertEqual(153067, beta2['installer_bytes'])
        self.assertEqual('d34a219723acf687fa685de4c8b9555de879be065b06b2457d3669f039f76e7b', beta2['installer_sha256'])
        self.assertEqual('099b372c76d6c15bb64a87a342fecc0952d15a67f036dd76179e3e56f4bafb7e', f3_inputs['inputs_sha256'])
        self.assertEqual(f3_inputs['inputs_sha256'], beta2['source_inputs_sha256'])
        self.assertEqual('0.1.1-beta.2', f3_inputs['version'])
        self.assertEqual(93, len(f3_inputs['files']))
        self.assertEqual([f['path'] for f in f3_inputs['files']], [f['path'] for f in manifest['files']])
        self.assertEqual(previous, beta2['previous_candidate'])
        self.assertEqual('failed-unattributed-cache-timeout-drift', beta2['existing_database_preservation'])
        self.assertEqual('local-candidate-tests-pass-database-preservation-blocked', beta2['status'])
        # F6/beta.3 stays frozen; stable promotion changes only approved metadata.
        beta3 = json.loads((ROOT / 'docs/release-candidate-beta3.json').read_text(encoding='utf-8'))
        f6_inputs = json.loads((ROOT / 'docs/release-f6-inputs.json').read_text(encoding='utf-8'))
        f6_body = {k: v for k, v in f6_inputs.items() if k != 'inputs_sha256'}
        self.assertEqual(common.digest(common.canonical(f6_body)), f6_inputs['inputs_sha256'])
        self.assertEqual('0.1.1-beta.3', beta3['version'])
        self.assertEqual('F6', beta3['candidate_stage'])
        self.assertTrue(beta3['github_prerelease'])
        self.assertEqual('v0.1.1-beta.3', beta3['proposed_tag'])
        self.assertEqual(common.SLUG + '-0.1.1-beta.3.zip', beta3['installer_filename'])
        self.assertEqual('0.1.1-beta.3', f6_inputs['version'])
        self.assertEqual(93, beta3['installer_files'])
        self.assertEqual(153396, beta3['installer_bytes'])
        self.assertEqual('11088f4dcb2ec06b4fab03cee2ab5e183b769d6fda5aeb3c4b8bc090ed55eb17', beta3['installer_sha256'])
        self.assertEqual('d9a39903db006973b8fa869ce8b58081922a6c91edfb0143f8dde8de5300dff8', f6_inputs['inputs_sha256'])
        self.assertEqual(f6_inputs['inputs_sha256'], beta3['source_inputs_sha256'])
        self.assertEqual(beta2, beta3['previous_candidate'])
        stable = json.loads((ROOT / 'docs/release-candidate-0.1.1.json').read_text(encoding='utf-8'))
        f8_inputs = json.loads((ROOT / 'docs/release-f8-inputs.json').read_text(encoding='utf-8'))
        self.assertEqual('0.1.1', stable['version'])
        self.assertEqual('F8', stable['candidate_stage'])
        self.assertFalse(stable['github_prerelease'])
        self.assertFalse(stable['runtime_behavior_changed'])
        self.assertEqual(153498, stable['installer_bytes'])
        self.assertEqual('2fbbeff021ce52daa1cc0de64729db008a531d353e88364708fa49db5c992d2e', stable['installer_sha256'])
        self.assertEqual('f19629b0c8ea1439675cc1f583a9f59e6ba6b586ba421e12224f891cf17fdf1f', stable['source_inputs_sha256'])
        self.assertEqual(stable['source_inputs_sha256'], f8_inputs['inputs_sha256'])
        self.assertEqual(common.digest(common.canonical({k:v for k,v in f8_inputs.items() if k != 'inputs_sha256'})), f8_inputs['inputs_sha256'])
        self.assertEqual(beta3, stable['previous_candidate'])
        accepted = json.loads((ROOT / 'docs/release-candidate-0.2.0-beta.1.json').read_text(encoding='utf-8'))
        g2_inputs = json.loads((ROOT / 'docs/release-g2-inputs.json').read_text(encoding='utf-8'))
        self.assertEqual('0.2.0-beta.1', accepted['version'])
        self.assertEqual('G2', accepted['candidate_stage'])
        self.assertEqual('4d870641d5a1ebd99de9562d89bf9e969441e10c141d78980080fa0b07ad6e72', accepted['installer_sha256'])
        self.assertEqual(156424, accepted['installer_bytes'])
        self.assertEqual('544118f38e048014f86c39550d4455fc911209e17ad4e602cdd38255d307b110', g2_inputs['inputs_sha256'])
        self.assertEqual(common.digest(common.canonical({k:v for k,v in g2_inputs.items() if k != 'inputs_sha256'})), g2_inputs['inputs_sha256'])
        self.assertEqual(accepted['source_inputs_sha256'], g2_inputs['inputs_sha256'])
        self.assertEqual(stable, accepted['previous_candidate'])
        self.assertEqual(accepted, candidate['previous_candidate'])
        self.assertEqual(stable['preservation_disposition'], accepted['preservation_disposition'])
        self.assertEqual(stable['accepted_runtime_limitations'], accepted['accepted_runtime_limitations'])
        self.assertEqual(beta3['preservation_disposition'], accepted['preservation_disposition'])
        self.assertEqual(beta3['accepted_runtime_limitations'], accepted['accepted_runtime_limitations'])
        accepted_files = {f['path']: f for f in g2_inputs['files']}
        self.assertEqual(sorted(accepted_files), [f['path'] for f in manifest['files']])
        self.assertEqual(['papijo-h5p-themer-wordpress.php', 'readme.txt'],
                         [f['path'] for f in manifest['files'] if f != accepted_files[f['path']]])
        beta_entry = _files[common.ENTRY].replace(b'0.2.1', b'0.2.0-beta.1')
        self.assertEqual(accepted_files[common.ENTRY]['sha256'], common.digest(beta_entry))
        old_files = {f['path']: f for f in f6_inputs['files']}
        self.assertEqual(sorted(old_files), [f['path'] for f in manifest['files']])
        self.assertEqual(['papijo-h5p-themer-wordpress.php', 'readme.txt'],
                         [f['path'] for f in f8_inputs['files'] if f != old_files[f['path']]])
        self.assertEqual('accepted-official-activity-viewer-only', candidate['administration_viewer_support'])
        self.assertEqual(manifest['inputs_sha256'], candidate['source_inputs_sha256'])
        self.assertEqual('G4', candidate['candidate_stage'])
        self.assertEqual('0.2.0-beta.1', candidate['runtime_behavior_comparison_baseline'])
        self.assertEqual('G3A-plugin-owned-state-preserved-whole-database-preservation-not-pass', candidate['existing_database_preservation'])
        disposition = candidate['preservation_disposition']
        self.assertEqual('ACCEPTED RESIDUAL RISK \u2014 NOT A RELEASE BLOCKER', disposition['decision'])
        self.assertTrue(disposition['project_owner_accepted_residual_uncertainty'])
        self.assertFalse(disposition['preservation_proven'])
        self.assertFalse(disposition['release_blocking'])
        self.assertFalse(disposition['full_database_preservation_pass'])
        self.assertEqual('unverified', disposition['attribution'])
        self.assertEqual(28, disposition['unchanged_schemas'])
        self.assertEqual((262, 263), (disposition['reported_next_auto_increment_before'], disposition['reported_next_auto_increment_after']))
        self.assertTrue(disposition['wp_options_row_fingerprint_changed'])
        self.assertFalse(disposition['database_modified_during_G4'])
        acceptance = accepted['manual_acceptance']
        self.assertEqual('0.1.1-beta.2', acceptance['candidate_version'])
        self.assertEqual('user-reported Firefox manual acceptance', acceptance['evidence_type'])
        self.assertEqual(25, acceptance['passed'])
        self.assertEqual(25, acceptance['total'])
        self.assertFalse(acceptance['independently_reproduced_by_codex'])
        current_acceptance = candidate['manual_acceptance']
        self.assertEqual('G3B', current_acceptance['stage'])
        self.assertEqual('0.2.0-beta.1', current_acceptance['candidate_version'])
        self.assertEqual('project-owner-reported Firefox manual acceptance', current_acceptance['evidence_type'])
        self.assertEqual((17, 17), (current_acceptance['passed'], current_acceptance['total']))
        self.assertFalse(current_acceptance['independently_reproduced_by_codex'])
        self.assertFalse(current_acceptance['applies_to_current_candidate_bytes'])
        self.assertTrue(current_acceptance['accepted_implementation_preserved_in_stable'])
        self.assertEqual(['G3B-' + str(i) for i in range(1, 18)], [r['id'] for r in current_acceptance['results']])
        self.assertEqual(['PASS'] * 17, [r['result'] for r in current_acceptance['results']])
        smoke = candidate['manual_smoke_acceptance']
        self.assertEqual('F7', smoke['stage'])
        self.assertEqual('0.1.1-beta.3', smoke['candidate_version'])
        self.assertEqual((3, 3), (smoke['passed'], smoke['total']))
        self.assertFalse(smoke['independently_reproduced_by_codex'])
        self.assertFalse(smoke['applies_to_current_candidate_bytes'])
        self.assertEqual('plugin-owned-innodb', candidate['storage_backend'])
        self.assertFalse(candidate['legacy_migration'])
        self.assertEqual(84, len(frozen['files']))
        self.assertEqual('fe3ad9c83d66eef9b9f517b33e0ec52ab0ceee44f023bd70dff32c414394e2a7',
                         common.digest(('\n'.join(f['path'] for f in frozen['files']) + '\n').encode()))
        working = json.loads((ROOT / 'docs/development-inputs.json').read_text(encoding='utf-8'))
        self.assertEqual(current_manifest, working)
        self.assertNotEqual(working['inputs_sha256'], frozen['inputs_sha256'])
        self.assertFalse(candidate['github_prerelease'])
        self.assertFalse(candidate['runtime_behavior_changed'])
        self.assertEqual('unresolved', candidate['slow_network_readiness_limitation'])
        self.assertFalse(candidate['slow_network_readiness_fully_verified'])
        self.assertEqual([accepted['accepted_runtime_limitations'][-1]], candidate['accepted_runtime_limitations'])
        readme = (ROOT / 'README.md').read_text(encoding='utf-8')
        acknowledgment = readme.split('## Acknowledgments\n', 1)[1].split('\n## ', 1)[0]
        expected = '''Papi Jo H5P Themer for WordPress was developed by Joseph Rézeau with assistance from ChatGPT and Codex (OpenAI) for technical discussions, implementation, testing, and code review.
The author also wishes to acknowledge previous work on H5P theming by:
- Oliver Tacke (SNORDIAN), for his H5P Themer plugin for WordPress.
- Matheus Mathias, for his H5P Themer plugin for Moodle.
These earlier projects contributed to the development of H5P theming solutions across different platforms.
Papi Jo H5P Themer for WordPress is an independent project and is not affiliated with or officially maintained by the authors of those earlier plugins.'''
        self.assertEqual(' '.join(expected.split()), ' '.join(acknowledgment.split()))

    def test_maintenance_candidate_preserves_published_contracts(self):
        files, manifest = packaging.audit(ROOT, '0.2.1')
        baseline = json.loads((ROOT / 'docs/release-g4-inputs.json').read_text())
        stable = json.loads((ROOT / 'docs/release-candidate-0.2.0.json').read_text())
        candidate = json.loads((ROOT / 'docs/release-candidate.json').read_text())
        self.assertEqual('3e04578111bdc34531bba139286ddc83145f09e6b0a1298bf42e43d36d59165f', stable['installer_sha256'])
        self.assertEqual(common.digest(common.canonical({k:v for k,v in baseline.items() if k != 'inputs_sha256'})), baseline['inputs_sha256'])
        old = {f['path']:f for f in baseline['files']}
        self.assertEqual(['includes/Admin/class-diagnostic-notices.php', common.ENTRY, 'readme.txt'],
                         [f['path'] for f in manifest['files'] if f != old[f['path']]])
        self.assertEqual(old[common.ENTRY]['sha256'], common.digest(files[common.ENTRY].replace(b'0.2.1', b'0.2.0')))
        self.assertEqual(stable, candidate['previous_candidate'])
        self.assertEqual('0.2.1', candidate['version'])
        self.assertEqual('v0.2.1', candidate['proposed_tag'])
        self.assertEqual('papijo-h5p-themer-wordpress-0.2.1.zip', candidate['installer_filename'])
        self.assertEqual(manifest['inputs_sha256'], candidate['source_inputs_sha256'])
        self.assertEqual(93, candidate['installer_files'])
        self.assertRegex(candidate['installer_sha256'], r'^[0-9a-f]{64}$')
        self.assertGreater(candidate['installer_bytes'], 0)
        self.assertFalse(candidate['manual_acceptance']['absent_warning_manually_rendered_in_firefox'])
        self.assertFalse(candidate['manual_acceptance']['applies_to_current_candidate_bytes'])
        for field in ['storage_backend', 'legacy_migration', 'runtime_behavior_changed', 'requires_php', 'requires_wordpress', 'accepted_runtime_limitations', 'preservation_disposition']:
            self.assertEqual(stable[field], candidate[field])

    def test_exact_synthetic_inventory(self):
        files, manifest = packaging.audit(self.root, '0.1.0')
        self.assertEqual(self.files, files)
        self.assertEqual(self.manifest(), manifest)

    def test_missing_runtime_profile_asset_or_license(self):
        for name in ['includes/Runtime/class-theme-attachment.php', 'includes/Profile/class-profile-installation.php',
                     'includes/Profile/class-profile-storage-admin.php', 'includes/owned-storage.php',
                     'includes/Storage/Owned/class-state-table.php', 'assets/js/theme-controller.js',
                     'assets/css/runtime-readiness.css', 'licenses/MIT-H5P-THEME-PICKER.txt']:
            with self.subTest(path=name):
                target = self.root / name
                body = target.read_bytes()
                target.unlink()
                with self.assertRaises((common.PolicyError, OSError)):
                    packaging.audit(self.root)
                target.write_bytes(body)

    def test_unexpected_production_and_backup_files(self):
        for name in ['includes/debug.php', 'assets/js/temp.js', 'presets/unreviewed.php', 'includes/config.php.bak', 'other.php']:
            with self.subTest(path=name):
                target = self.root / name
                target.write_bytes(b'Synthetic unexpected file')
                with self.assertRaises(common.PolicyError):
                    packaging.audit(self.root)
                target.unlink()

    def test_private_tree_is_opaque(self):
        private = self.root / '.local-acceptance'
        private.mkdir()
        (private / 'backup.sql').write_bytes(b'PRIVATE SENTINEL')
        with patch.object(Path, 'read_bytes', autospec=True, side_effect=AssertionError('No read')):
            self.assertNotIn('.local-acceptance/backup.sql', common.inventory(self.root))
        packaging.audit(self.root)

    def test_policy_attacks(self):
        original = (self.root / packaging.POLICY).read_text()
        cases = [original + '../escape.php\n', original + '.local-acceptance/private.json\n',
                 original + 'tests/worker.php\n', original.replace('LICENSE\n', ''),
                 original + 'LICENSE\n', original.replace('assets/js/admin.js', 'assets/js/ADMIN.js'),
                 original.replace('assets/js/admin.js', 'assets\\js\\admin.js')]
        for value in cases:
            with self.subTest(case=value[-60:]):
                (self.root / packaging.POLICY).write_text(value)
                with self.assertRaises(common.PolicyError):
                    packaging.audit(self.root)

    def test_path_vectors(self):
        for name in ['', '/absolute', '../secret', 'a/../b', 'a//b', './file', 'a\\b',
                     'a:b', 'file.', 'file ', 'CON', 'aux.txt', 'a/COM1.php', 'a/\x00b', 'a/\x7fb']:
            with self.subTest(path=repr(name)), self.assertRaises(common.PolicyError):
                common.relative_name(name)

    def test_duplicate_case_collision_policy(self):
        policy = self.root / 'docs/test-policy.txt'
        policy.write_text('A.txt\na.txt\n')
        with self.assertRaises(common.PolicyError):
            common.policy_paths(self.root, 'docs/test-policy.txt')

    def test_reparse_point_attribute_is_rejected(self):
        with patch.object(Path, 'lstat', return_value=SimpleNamespace(st_mode=stat.S_IFDIR,
                              st_file_attributes=stat.FILE_ATTRIBUTE_REPARSE_POINT)):
            with self.assertRaises(common.PolicyError):
                common.plain_path(self.root)

    def test_symbolic_link_component_is_rejected(self):
        with patch.object(Path, 'lstat', return_value=SimpleNamespace(st_mode=stat.S_IFLNK,
                              st_file_attributes=0)):
            with self.assertRaises(common.PolicyError):
                common.read_file(self.root, common.ENTRY)

    @unittest.skipUnless(os.name == 'nt', 'Native Windows junction check')
    def test_real_windows_junction_does_not_escape(self):
        target = Path(self.temp.name) / 'junction-target'
        target.mkdir()
        junction = self.root / 'includes/junction'
        env = dict(os.environ, PJH5PWP_JUNCTION=str(junction), PJH5PWP_TARGET=str(target))
        process = subprocess.run(['powershell.exe', '-NoProfile', '-Command',
            'New-Item -ItemType Junction -Path $env:PJH5PWP_JUNCTION -Target $env:PJH5PWP_TARGET | Out-Null'],
            env=env, capture_output=True, creationflags=subprocess.CREATE_NO_WINDOW)
        self.assertEqual(0, process.returncode, 'Native test junction creation failed')
        try:
            with self.assertRaises(common.PolicyError):
                packaging.audit(self.root)
        finally:
            # Remove only the new junction, never its target or an existing source path.
            self.assertEqual(self.root / 'includes/junction', junction)
            os.rmdir(junction)
        self.assertTrue(target.is_dir())

    def test_header_and_version_attacks(self):
        originals = dict(self.files)
        mutations = [(common.ENTRY, b'<?php // no WordPress header'),
                     (common.ENTRY, HEADER.replace('Requires PHP: 7.4', 'Requires PHP: 8.4').encode()),
                     (common.ENTRY, HEADER.replace('Requires at least: 6.5', 'Requires at least: 6.6').encode()),
                     (common.ENTRY, HEADER.replace("'0.1.0'", "'0.1.1-beta.1'").encode()),
                     (common.ENTRY, HEADER.replace(' * Version: 0.1.0', ' * Version: 0.1.0\n * Version: 0.1.0').encode()),
                     ('readme.txt', self.files['readme.txt'].replace(b'Stable tag: 0.1.0', b'Stable tag: 0.1.1')),
                     ('readme.txt', self.files['readme.txt'].replace(b'= 0.1.0 =', b'= 0.0.9 =')),
                     ('readme.txt', self.files['readme.txt'].replace(b'Requires PHP: 7.4', b'Requires PHP: 8.4'))]
        for name, body in mutations:
            with self.subTest(path=name):
                self.files = dict(originals, **{name: body})
                with self.assertRaises(common.PolicyError):
                    common.validate_payload(self.files)
        self.files = originals
        with self.assertRaises(common.PolicyError):
            common.validate_payload(self.files, '0.1.1-beta.1')

    def test_required_attribution_and_full_license_bytes(self):
        for name in [*common.LICENSE_DIGESTS, 'THIRD-PARTY-NOTICES.md']:
            with self.subTest(path=name):
                changed = dict(self.files, **{name: b'Truncated notice'})
                with self.assertRaises(common.PolicyError):
                    common.validate_payload(changed)

    def test_input_hash_detects_resource_change(self):
        before = self.manifest()
        changed = dict(self.files)
        changed['assets/js/admin.js'] += b'changed'
        self.assertNotEqual(before['inputs_sha256'], common.input_manifest(changed, common.validate_payload(changed))['inputs_sha256'])

    def test_deterministic_order_metadata_and_integrity(self):
        first = packaging.archive_bytes(self.files)
        second = packaging.archive_bytes(dict(reversed(list(self.files.items()))))
        self.assertEqual(first, second)
        report = packaging.verify_archive(first, self.files, self.manifest())
        self.assertEqual(93, report['members'])
        self.assertEqual(common.digest(first), report['sha256'])
        with zipfile.ZipFile(BytesIO(first)) as archive:
            self.assertIsNone(archive.testzip())
            self.assertEqual([common.SLUG + '/' + p for p in sorted(self.files)], archive.namelist())
            self.assertTrue(all(i.date_time == packaging.STAMP for i in archive.infolist()))
        self.assertFalse(list(Path(self.temp.name).rglob('*.zip')))

    def test_extra_unsafe_or_duplicate_archive_members(self):
        for name in ['other/file', common.SLUG + '/../escape', common.SLUG + '/tests/worker.php',
                     common.SLUG + '/.local-acceptance/backup.sql', common.SLUG + '/' + common.ENTRY,
                     common.SLUG + '/includes\\unsafe.php']:
            with self.subTest(path=name):
                data = self.modified_archive(extra=(name, b'extra'))
                with self.assertRaises(common.PolicyError):
                    packaging.verify_archive(data, self.files, self.manifest())

    def test_archive_content_changed_or_missing(self):
        changed = self.modified_archive(transform=lambda i, b: (i, b + b'changed'))
        missing = packaging.archive_bytes({p: b for p, b in self.files.items() if p != common.ENTRY})
        for data in [changed, missing]:
            with self.assertRaises(common.PolicyError):
                packaging.verify_archive(data, self.files, self.manifest())

    def test_noncanonical_archive_metadata(self):
        for field, value in [('date_time', (2020, 1, 1, 0, 0, 0)),
                             ('external_attr', (stat.S_IFLNK | 0o777) << 16),
                             ('extra', b'\x01\x00\x00\x00'), ('comment', b'unexpected')]:
            def mutate(info, data):
                setattr(info, field, value)
                return info, data
            with self.subTest(field=field), self.assertRaises(common.PolicyError):
                packaging.verify_archive(self.modified_archive(transform=mutate), self.files, self.manifest())

    def test_corruption_and_trailing_payload(self):
        archive = packaging.archive_bytes(self.files)
        for data in [b'not a ZIP', archive[:-40], archive + b'private trailing data']:
            with self.assertRaises(common.PolicyError):
                packaging.verify_archive(data, self.files, self.manifest())

    def test_crc_and_encryption_flags_are_rejected(self):
        original = packaging.archive_bytes(self.files)
        central = original.index(b'PK\x01\x02')
        corrupt = bytearray(original)
        corrupt[central + 16] ^= 1  # Central-directory CRC, preserving lengths/paths.
        encrypted = bytearray(original)
        encrypted[central + 8] |= 1
        for data in [bytes(corrupt), bytes(encrypted)]:
            with self.assertRaises(common.PolicyError):
                packaging.verify_archive(data, self.files, self.manifest())

    def test_prerelease_version_syntax(self):
        self.assertTrue(common.valid_version('0.1.1-beta.1'))
        self.assertTrue(common.valid_version('0.1.1-beta.2'))
        for value in ['0.1.1-beta.01', '0.01.1', '../0.1.1', '0.1.1-', '0.1.1-a..b']:
            self.assertFalse(common.valid_version(value))

    def test_build_requires_matching_reviewed_manifest(self):
        stale = self.manifest()
        stale['version'] = '0.1.1-beta.1'
        with self.assertRaises(common.PolicyError):
            packaging.build(self.root, '0.1.0', stale, Path(self.temp.name) / 'new-output')
        self.assertFalse(list(Path(self.temp.name).rglob('*.zip')))

    def test_build_sidecars_without_writing_zip(self):
        captured = {}
        def capture(path, data):
            captured[Path(path).name] = data
        output = Path(self.temp.name) / 'new-output'
        with patch.object(packaging, 'write_new', side_effect=capture):
            result = packaging.build(self.root, '0.1.0', self.manifest(), output)
        name = common.SLUG + '-0.1.0.zip'
        self.assertEqual(result['sha256'], common.digest(captured[name]))
        self.assertEqual(self.manifest(), json.loads(captured['inputs.json']))
        self.assertIn(result['sha256'].encode(), captured[name + '.sha256'])
        self.assertEqual('0.1.0', json.loads(captured['verification.json'])['version'])
        self.assertFalse(list(output.iterdir()))

    def test_output_refuses_existing_and_symlink_parent(self):
        path = self.root / 'existing.json'
        path.write_bytes(b'preserve')
        with self.assertRaises(common.PolicyError):
            common.write_new(path, b'replace')
        self.assertEqual(b'preserve', path.read_bytes())
        with patch.object(Path, 'lstat', return_value=SimpleNamespace(st_mode=stat.S_IFLNK, st_file_attributes=0)):
            with self.assertRaises(common.PolicyError):
                common.write_new(self.root / 'new.json', b'new')

    def test_document_projection_retains_history_and_commit_pins(self):
        source = ('# Historical report\n\nSource only; no deployment at that checkpoint.\n'
                  + 'Local: `Q' + ':/Us' + 'ers/developer/project/file.php`\n'
                  + 'Evidence: `.' + 'local-acceptance/private-run/report.json`\n'
                  + 'Checksum: `' + 'a' * 64 + '`\n'
                  + 'Backdrop commit d9b53d706a94dac322b89885bf668145ab3803c4\n'
                  + 'Slow-network readiness remains unresolved.\n')
        projected, changed = public.project_document(source.encode())
        self.assertTrue(changed)
        self.assertIn(b'Source only; no deployment at that checkpoint.', projected)
        self.assertIn(b'd9b53d706a94dac322b89885bf668145ab3803c4', projected)
        self.assertIn(b'Slow-network readiness remains unresolved.', projected)
        self.assertNotIn(b'private-run', projected)
        self.assertNotIn(b'developer/project', projected)
        self.assertEqual((projected, False), public.project_document(projected))

    def test_private_link_becomes_text_and_public_links_stay_valid(self):
        original = ('# Title\n\n[Evidence](../.' + 'local-acceptance/private/report.json)\n[Architecture](architecture.md)\n').encode()
        projected, _ = public.project_document(original)
        public.validate_links({'docs/test.md': projected, 'docs/architecture.md': b'# Architecture\n'})
        self.assertNotIn(b'private/report.json', projected)
        with self.assertRaises(common.PolicyError):
            public.validate_links({'docs/test.md': b'[Missing](absent.md)'})

    def test_public_secret_scan_negative_vectors(self):
        for value in [('ghp_' + 'A' * 32).encode(), ('-----BEGIN ' + 'PRIVATE KEY-----').encode(),
                      ("define('DB_" + "PASSWORD', 'synthetic-secret');").encode(),
                      ('Q' + ':/Us' + 'ers/developer/private.php').encode()]:
            with self.assertRaises(common.PolicyError):
                public.audit_public_bytes('docs/test.md', value)

    def test_public_path_policy_excludes_private_and_generated_files(self):
        for value in ['.local-acceptance/evidence.json', 'wp-config.php', '.env', 'backup.sql',
                      'build/output.json', 'private.private.json', 'tests/temp.log', 'dist/output.zip']:
            with self.subTest(path=value):
                self.assertTrue(common.denied(value))
        for value in ['tests/fixtures/plugins/h5p/h5p.php', 'docs/architecture.md', 'tools/package_release.py']:
            self.assertFalse(common.denied(value))

    def test_public_inventory_rejects_unreviewed_and_missing_development_source(self):
        actual = common.inventory(ROOT)
        for changed in [actual + ['tests/unreviewed.php'], [p for p in actual if p != 'docs/architecture.md']]:
            with patch.object(public, 'inventory', return_value=changed):
                with self.assertRaises(common.PolicyError):
                    public.prepare(ROOT)

    def test_projection_writes_only_fresh_isolated_source_directory(self):
        files = {'README.md': b'# Public fixture\n', 'docs/architecture.md': b'# Architecture\n'}
        destination = Path(self.temp.name) / 'public-source'
        public.write_projection(files, destination, self.root)
        self.assertEqual(sorted(files), common.inventory(destination))
        with self.assertRaises(common.PolicyError):
            public.write_projection(files, destination, self.root)
        with self.assertRaises(common.PolicyError):
            public.write_projection(files, self.root / 'includes/public-source', self.root)
        self.assertFalse((self.root / 'includes/public-source').exists())
        self.assertFalse(list(Path(self.temp.name).rglob('*.zip')))

    def test_complete_real_public_projection_and_production_identity(self):
        files, report = public.prepare(ROOT)
        self.assertEqual(93, report['production_files_unchanged'])
        self.assertTrue(any(p.startswith('tests/fixtures/') for p in files))
        self.assertTrue(any(p.startswith('docs/phase-') for p in files))
        self.assertIn('tools/package_release.py', files)
        self.assertIn('CONTRIBUTING.md', files)
        for path, data in packaging.audit(ROOT)[0].items():
            self.assertEqual(data, files[path])


if __name__ == '__main__':
    unittest.main(verbosity=2)
