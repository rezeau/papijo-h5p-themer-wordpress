<?php
/** Import service + real adapter against isolated SQL doubles. No live CMS/DB. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-import.php';
require dirname( __DIR__ ) . '/storage/fake-database.php';
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Transfer\Theme_JSON;
use Papijo\H5PThemerWordPress\Transfer\Import_Planner;
use Papijo\H5PThemerWordPress\Transfer\Import_Service;
use Papijo\H5PThemerWordPress\Transfer\Import_Storage_Compatibility;
use Papijo\H5PThemerWordPress\Tests\Fake_Database;

$checks = 0; $cap = true; $multisite = false;
function current_user_can( $capability ) { global $cap; check( 'manage_options' === $capability, 'Existing management capability' ); return $cap; }
function is_multisite() { global $multisite; return $multisite; }
function check( $value, $message ) { global $checks; if ( ! $value ) { throw new RuntimeException( $message ); } ++$checks; }
function ok( $r ) { check( array() === $r['errors'], 'Expected success: ' . implode( ',', $r['errors'] ) ); return $r['data']; }
function bad( $r, $code, $outcome = 'not_committed' ) { check( null === $r['data'] && array( $code ) === $r['errors'] && $outcome === $r['outcome'], 'Expected failure: ' . $code ); }
function record( $id = 'ocean', $base = 'mint' ) { return array( 'id' => $id, 'label' => 'Été <colors>', 'description' => "Lines\nexact", 'base_preset' => null === $base ? null : array( 'id' => $base, 'version' => Preset_Catalog::VERSION ), 'colors' => array( '--h5p-theme-background' => '#aBc' ) ); }
function wire( $records ) { return ok( ( new Theme_JSON() )->serialize( $records ) ); }
function fixture( $invalidate = null ) {
	$db = new Fake_Database(); $state = Schema::initial();
	$definition = ok( ( new Theme_Operations() )->duplicate( array( 'kind' => 'builtin', 'id' => 'dark', 'version' => Preset_Catalog::VERSION ), 'existing', 'Existing' ) );
	$definition['density'] = 'small';
	$state = ok( ( new Library_Changes() )->create( $state, $definition ) );
	$state = ok( ( new Library_Changes() )->set_default( $state, array( 'kind' => 'custom', 'id' => 'existing' ) ) );
	$db->rows[Schema::OPTION] = array( 'option_id' => '1', 'option_value' => serialize( $state ), 'autoload' => 'off' );
	$db->rows['pjh5pwp_role_permissions'] = array( 'option_id' => '2', 'option_value' => 'role sentinel' );
	$db->rows['profile_fixture'] = array( 'option_id' => '3', 'option_value' => serialize( array( 'theme' => 'existing', 'density' => 'medium', 'allow_theme' => '1', 'allow_density' => '1' ) ) );
	$store = new WordPress_Snapshot_Store( array( $db, 'open' ), $invalidate ?: static function () {} );
	return array( $db, $store, new Import_Service( $store ) );
}
function updates( $db ) { return count( array_filter( $db->log, static function ( $sql ) { return 0 === strpos( $sql, 'UPDATE' ); } ) ); }

list( $db, $store, $service ) = fixture(); $before = $store->read(); $foreign = $db->rows;
$json = wire( array( record(), record( 'core_accent', null ) ) );
$plan = ok( $service->prepare( $json ) ); check( false === $plan['directly_persistable'] && false === strpos( json_encode( $plan['records'] ), 'density' ), 'Detached plan unchanged and colors-only' );
check( $before === $store->read() && 0 === updates( $db ), 'Preparation is read only' );
$result = $service->commit( $plan ); $summary = ok( $result ); $after = $store->read();
check( 'committed' === $result['outcome'] && 2 === $summary['count'] && array( 'core_accent', 'ocean' ) === $summary['ids'], 'Whole batch acknowledged' );
check( 1 === updates( $db ) && $before['revision'] + 1 === $after['revision'], 'One SQL update and one revision per complete bundle' );
check( $before['definitions']['existing'] === $after['definitions']['existing'] && $before['settings'] === $after['settings'], 'Existing definition/default exact preservation' );
check( $foreign['profile_fixture'] === $db->rows['profile_fixture'] && $foreign['pjh5pwp_role_permissions'] === $db->rows['pjh5pwp_role_permissions'], 'Foreign profiles/Density/roles untouched' );
foreach ( $plan['records'] as $r ) {
	$d = $after['definitions'][$r['id']]; check( false === $after['publication']['themes']['custom:' . $r['id']], 'Initially unpublished' );
	check( $r['colors'] === $d['colors'] && 'large' === $d['density'], 'Sparse overrides plus established storage-only metadata' );
	check( ( null === $r['base_preset'] ? 1 : 2 ) === $d['schema_version'], 'Existing schema represents exact inheritance' );
	$resolved = ok( ( new Palette_Resolver() )->resolve( array( 'kind' => 'custom', 'id' => $r['id'] ), $after['definitions'] ) );
	check( ! array_key_exists( 'density', $resolved ), 'Palette resolution has no Density' );
}
check( $json === ok( ( new Theme_JSON() )->export( $after, $summary['ids'] ) ), 'Import/export exact colors-only roundtrip' );
bad( $service->commit( $plan ), 'invalid_import_plan' );
foreach ( array( 1, 2 ) as $schema ) {
	foreach ( array( 'small', 'medium', 'large' ) as $legacy ) {
		list( $db, $store, $service ) = fixture(); $native = record(); $native['_config_name'] = 'h5p_themer.theme.ocean'; $native['schema_version'] = $schema; $native['density'] = $legacy; $native['_config_static'] = false; $native['colors'] = (object) $native['colors'];
		if ( 1 === $schema ) { unset( $native['base_preset'] ); }
		$plan = ok( $service->prepare( json_encode( $native ) ) ); ok( $service->commit( $plan ) );
		check( Theme_Operations::LEGACY_DENSITY_DEFAULT === $store->read()['definitions']['ocean']['density'], 'Native Density cannot choose stored compatibility metadata' );
	}
}
list( $db, $store, $service ) = fixture();
$copy = ok( ( new Theme_Operations() )->duplicate( array( 'kind' => 'builtin', 'id' => 'mint', 'version' => Preset_Catalog::VERSION ), 'copy', 'Copy' ) );
check( 'large' === $copy['density'] && 'large' === Theme_Operations::LEGACY_DENSITY_DEFAULT, 'Existing default verified rather than invented' );
$core = record( 'plain', null ); unset( $core['description'] ); $saved = ok( ( new Import_Storage_Compatibility() )->definition( $core ) ); check( ! isset( $saved['description'], $saved['base_preset'] ), 'Optional absence/Core semantics retained' );
ok( $service->commit( ok( $service->prepare( wire( array( record( 'mint' ) ) ) ) ) ) );
check( isset( $store->read()['definitions']['mint'] ) && ok( ( new Preset_Catalog() )->get( 'mint', Preset_Catalog::VERSION ) )['id'] === 'mint', 'Builtin ID is separate custom namespace, catalog immutable' );
bad( $service->prepare( wire( array( record( 'existing' ) ) ) ), 'destination_unavailable' );
foreach ( array( '', '{}', '[]', '{bad', str_repeat( ' ', 1048577 ), str_repeat( '[', 20 ) . '0' . str_repeat( ']', 20 ) ) as $invalid ) { $log = $db->log; bad( $service->prepare( $invalid ), 'invalid_import_input' ); check( $log === $db->log, 'Invalid input never reads/writes repository' ); }
$raw = json_decode( wire( array( record() ) ) ); $raw->themes[] = $raw->themes[0]; bad( $service->prepare( json_encode( $raw ) ), 'invalid_import_input' );
$raw->themes[1] = clone $raw->themes[0]; $raw->themes[1]->id = 'second'; $raw->themes[1]->colors->{'--h5p-theme-background'} = 'red'; bad( $service->prepare( json_encode( $raw ) ), 'invalid_import_input' );
$plan = ok( $service->prepare( wire( array( record( 'fresh' ) ) ) ) );
foreach ( array( 'records', 'expected_revision', 'snapshot_context', 'input_digest', 'publication_intents', 'collisions', 'directly_persistable', 'kind' ) as $field ) {
	$plan = ok( $service->prepare( wire( array( record( 'fresh' ) ) ) ) ); $forged = $plan; $forged[$field] = 'forged'; $before = $db->rows;
	bad( $service->commit( $forged ), 'invalid_import_plan' ); check( $before === $db->rows, 'Modified plan cannot write' );
}
$plan = ok( $service->prepare( wire( array( record( 'fresh' ) ) ) ) ); bad( ( new Import_Service( $store ) )->commit( $plan ), 'invalid_import_plan' );
bad( $service->commit( new stdClass() ), 'invalid_import_plan' );
$calls = 0; $revoked = new Import_Service( $store, static function () use ( &$calls ) { return ++$calls < 5; } );
$plan = ok( $revoked->prepare( wire( array( record( 'revoked' ) ) ) ) ); $before = $db->rows;
bad( $revoked->commit( $plan ), 'forbidden' ); check( $before === $db->rows, 'Permission revoked immediately before CAS prevents write' );
$auth = true; $guarded = new Import_Service( $store, static function () use ( &$auth ) { return $auth; } ); $plan = ok( $guarded->prepare( wire( array( record( 'guarded' ) ) ) ) ); $auth = false;
bad( $guarded->commit( $plan ), 'forbidden' ); $before = $db->log; bad( $guarded->prepare( 'not JSON' ), 'forbidden' ); check( $before === $db->log, 'Unauthorized cannot inspect repository' );
$cap = false; bad( $service->prepare( '{}' ), 'forbidden' ); $cap = true; $multisite = true; bad( $service->prepare( '{}' ), 'forbidden' ); $multisite = false;
bad( ( new Import_Service( $store, static function () { return 1; } ) )->prepare( '{}' ), 'forbidden' );
bad( ( new Import_Service( $store, static function () { throw new RuntimeException( 'SECRET' ); } ) )->prepare( '{}' ), 'forbidden' );
// A stale plan and a same-revision context change both fail before CAS.
foreach ( array( 'revision', 'bytes' ) as $race ) {
	list( $db, $store, $service ) = fixture(); $plan = ok( $service->prepare( $json ) ); $changed = $store->read(); if ( 'revision' === $race ) { ++$changed['revision']; } else { $changed['publication']['themes']['builtin:dark'] = false; }
	$db->rows[Schema::OPTION]['option_value'] = serialize( $changed ); bad( $service->commit( $plan ), 'storage_conflict' ); check( 0 === updates( $db ), 'Stale/context conflict before write' );
}
// Race AFTER the store's internal read, immediately before its conditional SQL.
foreach ( array( 'overlap', 'disjoint' ) as $race ) {
	list( $db, $store, $first ) = fixture(); $second = new Import_Service( $store ); $a = ok( $first->prepare( $json ) );
	$b = ok( $second->prepare( wire( array( record( 'overlap' === $race ? 'ocean' : 'other' ), record( 'winner_only' ) ) ) ) );
	$db->before_write = static function () use ( $second, $b ) { ok( $second->commit( $b ) ); };
	bad( $first->commit( $a ), 'storage_conflict' ); $final = $store->read();
	check( isset( $final['definitions']['winner_only'] ) && ! isset( $final['definitions']['core_accent'] ), 'Only whole winning batch persisted' );
}
foreach ( array( 'write', 'lost_ack', 'after_commit' ) as $fault ) {
	list( $db, $store, $service ) = fixture(); $plan = ok( $service->prepare( $json ) ); $before = $db->rows; $db->fault = $fault;
	$r = $service->commit( $plan ); bad( $r, 'write' === $fault ? 'import_write_failed' : 'import_write_unknown', 'write' === $fault ? 'not_committed' : 'unknown' );
	$db->fault = null; $final = $store->read(); check( 1 === updates( $db ), 'Fault causes no per-theme save or retry' );
	check( 'write' === $fault ? $db->rows === $before : isset( $final['definitions']['ocean'], $final['definitions']['core_accent'] ), 'Known failure preserves old state; lost acknowledgement leaves whole batch, never partial' );
	bad( $service->commit( $plan ), 'invalid_import_plan' );
}
list( $db, $store, $service ) = fixture( static function () { throw new RuntimeException( 'SECRET cache' ); } );
$plan = ok( $service->prepare( $json ) ); bad( $service->commit( $plan ), 'import_committed_cache_failed', 'committed' ); check( isset( $store->read()['definitions']['ocean'], $store->read()['definitions']['core_accent'] ), 'Committed cache failure never falsely reports rollback' );
list( $db, $store, $service ) = fixture(); $db->fault = 'read'; bad( $service->prepare( $json ), 'import_storage_unavailable' );
list( $db, $store, $service ) = fixture(); $state = $store->read(); $defs = array();
// One remaining revision can accept many records; intermediate revisions are not consumed.
$state['revision'] = PHP_INT_MAX - 1; $defs[] = ok( ( new Import_Storage_Compatibility() )->definition( record() ) ); $defs[] = ok( ( new Import_Storage_Compatibility() )->definition( record( 'second' ) ) );
check( PHP_INT_MAX === ok( ( new Library_Changes() )->create_many( $state, $defs ) )['revision'], 'One revision per batch at overflow boundary' );
$invalid = $defs; $invalid[1]['colors'] = array( '--evil' => '#fff' ); $unchanged = serialize( $state ); $r = ( new Library_Changes() )->create_many( $state, $invalid ); check( null === $r['data'] && $r['errors'] && $unchanged === serialize( $state ), 'Invalid final record gives no partial snapshot' );
$r = ( new Library_Changes() )->create_many( $state, array() ); check( null === $r['data'] && array( 'invalid_definition_batch' ) === $r['errors'], 'Empty batch cannot consume a revision' );
$r = ( new Library_Changes() )->create_many( $state, array( $defs[0], $defs[0] ) ); check( null === $r['data'] && $r['errors'], 'Domain batch duplicate rejection' );
// Stored size limit includes pre-existing themes, not only the bounded JSON.
$state = Schema::initial(); for ( $i = 0; $i < 940; ++$i ) { $d = $defs[0]; $d['id'] = 'old_' . $i; $d['description'] = str_repeat( 'x', 4096 ); $state['definitions'][$d['id']] = $d; }
$db->rows[Schema::OPTION]['option_value'] = serialize( $state );
if ( strlen( serialize( $state ) ) < 4194304 ) {
	$records = array(); for ( $i = 0; $i < 100; ++$i ) { $r = record( 'new_' . $i ); $r['description'] = str_repeat( 'x', 4096 ); $records[] = $r; }
	$plan = ok( $service->prepare( wire( $records ) ) ); bad( $service->commit( $plan ), 'import_storage_limit' ); check( 0 === updates( $db ), 'Oversize next aggregate rejected before write' );
} else { throw new RuntimeException( 'Size fixture must start below storage cap' ); }
echo 'PASS import persistence doubles: ' . $checks . " assertions; no CMS or live database.\n";
