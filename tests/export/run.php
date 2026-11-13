<?php
/** Source-only export application tests. No CMS, database, writes or HTTP delivery. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-export.php';
use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Transfer\Export_Service;
use Papijo\H5PThemerWordPress\Transfer\Theme_JSON;
use Papijo\H5PThemerWordPress\Transfer\JSON_Decoder;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;

final class Export_Test_Store implements Snapshot_Store {
	public $snapshot; public $reads = 0; public $fault = false;
	public function __construct( $snapshot ) { $this->snapshot = $snapshot; }
	public function read() { ++$this->reads; if ( $this->fault ) { throw new RuntimeException( 'SECRET credential SQL /private/path' ); } return $this->snapshot; }
	public function compare_and_swap( $revision, array $next ) { throw new RuntimeException( 'Export must never write' ); }
}
$checks = 0; $capability = true; $multisite = false; $caps = array();
function current_user_can( $cap ) { global $capability, $caps; $caps[] = $cap; return $capability; }
function is_multisite() { global $multisite; return $multisite; }
function get_user_meta() { throw new RuntimeException( 'Preferences must not be read' ); }
function update_option() { throw new RuntimeException( 'No option writes' ); }
function update_user_meta() { throw new RuntimeException( 'No user writes' ); }
function check( $value, $message ) { global $checks; if ( ! $value ) { throw new RuntimeException( $message ); } ++$checks; }
function ok( $result ) { check( array() === $result['errors'], 'Expected export success: ' . implode( ',', $result['errors'] ) ); return $result['data']; }
function bad( $result, $code ) { check( null === $result['data'] && array( $code ) === $result['errors'], 'Atomic rejection: ' . $code ); }
function definition( $id, $schema = 2 ) {
	$r = array( 'schema_version' => $schema, 'id' => $id, 'label' => "Dangerous \" name / \\ <script>é</script>", 'description' => "Sparse\ncolors", 'density' => 'small', 'colors' => array( '--h5p-theme-background' => '#aBc' ) );
	if ( 2 === $schema ) { $r['base_preset'] = array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ); }
	return $r;
}
$state = Schema::initial();
$state['revision'] = 42;
$state['definitions'] = array( 'zebra' => definition( 'zebra' ), 'amber' => definition( 'amber', 1 ), 'mint' => definition( 'mint' ) );
$state['definitions']['mint']['colors'] = array(); unset( $state['definitions']['mint']['description'] );
$state['settings']['default_theme'] = array( 'kind' => 'custom', 'id' => 'zebra' );
$state['publication']['themes'] = array( 'custom:zebra' => false, 'custom:amber' => true );
$store = new Export_Test_Store( $state ); $service = new Export_Service( $store );
$single = array( 'mode' => 'single', 'id' => 'zebra' );
$before = serialize( $store->snapshot ); $profiles = array( 'density' => 'medium', 'theme' => 'site_default' ); $roles = array( 'subscriber' => true ); $outside = serialize( array( $profiles, $roles ) );
$headers = headers_list(); ob_start(); $one = ok( $service->prepare( $single ) ); $output = ob_get_clean();
check( '' === $output && headers_list() === $headers, 'No output or HTTP headers' );
check( 1 === $store->reads, 'One authoritative read per export' );
check( 'application/json' === $one['mime_type'] && 'UTF-8' === $one['encoding'], 'JSON MIME and UTF-8 encoding' );
check( 'attachment' === $one['disposition'] && 'private, no-store' === $one['cache_control'] && true === $one['nosniff'], 'Private safe disposition metadata' );
check( 'papijo-h5p-theme-colors-zebra.json' === $one['filename'], 'Single filename uses stable ID only' );
check( $one['json'] === ok( ( new Theme_JSON() )->export( $state, array( 'zebra' ) ) ), 'Exactly the unchanged Phase 3B serializer' );
$parsed = ok( ( new Theme_JSON() )->parse( $one['json'] ) );
check( array( '--h5p-theme-background' => '#aBc' ) === $parsed[0]['colors'], 'Sparse spelling preserved, not resolved palette' );
check( $state['definitions']['zebra']['base_preset'] === $parsed[0]['base_preset'], 'Exact preset ID/version' );
check( $state['definitions']['zebra']['label'] === $parsed[0]['label'] && $state['definitions']['zebra']['description'] === $parsed[0]['description'], 'Name and description preserved as JSON data' );
$selected = ok( $service->prepare( array( 'mode' => 'selected', 'ids' => array( 'zebra', 'amber' ) ) ) );
check( 'papijo-h5p-theme-colors.json' === $selected['filename'], 'Bulk filename independent of names/order' );
check( $selected === ok( $service->prepare( array( 'mode' => 'selected', 'ids' => array( 'amber', 'zebra' ) ) ) ), 'Deterministic complete download contract' );
$all = ok( $service->prepare( array( 'mode' => 'all' ) ) ); $all_records = ok( ( new Theme_JSON() )->parse( $all['json'] ) );
check( array( 'amber', 'mint', 'zebra' ) === array_column( $all_records, 'id' ), 'All custom IDs in stable order' );
check( null === $all_records[0]['base_preset'], 'Legacy sparse Core inheritance preserved' );
check( array() === $all_records[1]['colors'] && ! array_key_exists( 'description', $all_records[1] ), 'Empty overrides and absent description preserved' );
check( false === $state['publication']['themes']['custom:zebra'] && count( $all_records ) === 3, 'Published and unpublished customs eligible' );
check( 'mint' === $all_records[1]['id'], 'Same-ID custom exported without exporting built-in' );
foreach ( array( 'density', 'publication', 'default_theme', 'runtime_enabled', 'allow_theme', 'roles', 'credentials', 'settings', 'revision' ) as $key ) {
	check( false === strpos( $all['json'], '"' . $key . '"' ), 'No private field: ' . $key );
}
$store->snapshot['definitions'] = array_reverse( $state['definitions'], true );
check( $all === ok( $service->prepare( array( 'mode' => 'all' ) ) ), 'Repository iteration order has no effect' ); $store->snapshot = $state;
bad( $service->prepare( array( 'mode' => 'single', 'id' => 'dark' ) ), 'missing_custom_theme' );
bad( $service->prepare( array( 'mode' => 'selected', 'ids' => array( 'zebra', 'missing' ) ) ), 'missing_custom_theme' );
foreach ( array( null, true, 'all', array(), array( 'mode' => 'other' ), array( 'mode' => array() ), array( 'mode' => 'all', 'ids' => array() ), array( 'mode' => 'single' ), array( 'mode' => 'single', 'id' => 'builtin:mint' ), array( 'mode' => 'single', 'id' => 'core' . "\r\n" ), array( 'mode' => 'single', 'id' => '../path' ), array( 'mode' => 'single', 'id' => array() ), array( 'mode' => 'selected', 'ids' => array() ), array( 'mode' => 'selected', 'ids' => array( 'key' => 'zebra' ) ), array( 'mode' => 'selected', 'ids' => array_fill( 0, 101, 'zebra' ) ) ) as $selection ) {
	$reads = $store->reads; bad( $service->prepare( $selection ), 'invalid_export_selection' ); check( $reads === $store->reads, 'Invalid selection rejected before read' );
}
$reads = $store->reads; bad( $service->prepare( array( 'mode' => 'selected', 'ids' => array( 'zebra', 'zebra' ) ) ), 'duplicate_export_selection' ); check( $reads === $store->reads, 'Duplicates rejected before read' );
$capability = false; $reads = $store->reads; bad( $service->prepare( $single ), 'forbidden' ); bad( $service->prepare( null ), 'forbidden' ); check( $reads === $store->reads, 'Unauthorized receives neither data nor selection/storage diagnostics' ); $capability = true;
$multisite = true; bad( $service->prepare( $single ), 'forbidden' ); check( $reads === $store->reads, 'Multisite blocked before read' ); $multisite = false;
check( array( 'manage_options' ) === array_values( array_unique( $caps ) ), 'Uses existing management capability only' );
foreach ( array( 1, 'yes', null, false ) as $admitted ) { bad( ( new Export_Service( $store, static function () use ( $admitted ) { return $admitted; } ) )->prepare( $single ), 'forbidden' ); }
bad( ( new Export_Service( $store, static function () { throw new RuntimeException( 'SECRET' ); } ) )->prepare( $single ), 'forbidden' );
$calls = 0; $revoked = new Export_Service( $store, static function () use ( &$calls ) { return 1 === ++$calls; } ); bad( $revoked->prepare( $single ), 'forbidden' );
$store->fault = true; bad( $service->prepare( $single ), 'export_storage_unavailable' ); $store->fault = false;
$store->snapshot = null; bad( $service->prepare( $single ), 'storage_absent' );
foreach ( array( array( 'schema_version' => 2 ), false, 'SECRET', array_replace( $state, array( 'revision' => -1 ) ) ) as $broken ) { $store->snapshot = $broken; bad( $service->prepare( $single ), 'invalid_theme_library' ); }
foreach ( array( 'label' => '', 'density' => 'wide', 'colors' => array( '--evil' => '#fff' ), 'base_preset' => array( 'id' => 'mint', 'version' => 'unknown' ) ) as $key => $value ) { $store->snapshot = $state; $store->snapshot['definitions']['amber'][$key] = $value; bad( $service->prepare( $single ), 'invalid_theme_library' ); }
$empty = Schema::initial(); $store->snapshot = $empty; bad( $service->prepare( array( 'mode' => 'all' ) ), 'empty_export_selection' );
$large = Schema::initial(); for ( $i = 0; $i < 100; ++$i ) { $id = 'custom_' . $i; $large['definitions'][$id] = definition( $id ); }
$store->snapshot = $large; $large_output = ok( $service->prepare( array( 'mode' => 'all' ) ) ); check( 100 === count( ok( ( new Theme_JSON() )->parse( $large_output['json'] ) ) ), 'Exact 100-record boundary' );
check( strlen( $large_output['json'] ) <= JSON_Decoder::MAX_BYTES, 'Phase 3B byte limit preserved' );
$large['definitions']['extra'] = definition( 'extra' ); $store->snapshot = $large; bad( $service->prepare( array( 'mode' => 'all' ) ), 'invalid_theme_count' );
// Explicit bounded selection remains valid in a larger library; no silent all-export truncation.
ok( $service->prepare( array( 'mode' => 'single', 'id' => 'extra' ) ) );
$long = str_repeat( 'a', 64 ); $store->snapshot = Schema::initial(); $store->snapshot['definitions'][$long] = definition( $long );
$download = ok( $service->prepare( array( 'mode' => 'single', 'id' => $long ) ) ); check( 1 === preg_match( '/\Apapijo-h5p-theme-colors-[a-z][a-z0-9_]{0,63}\.json\z/', $download['filename'] ), 'Longest valid ID yields safe ASCII filename' );
$store->snapshot = $state; $legacy_change = $state; $legacy_change['definitions']['zebra']['density'] = 'large'; $other = new Export_Test_Store( $legacy_change );
check( $one === ok( ( new Export_Service( $other ) )->prepare( $single ) ), 'Density-only storage change cannot affect download' );
check( $before === serialize( $store->snapshot ) && $outside === serialize( array( $profiles, $roles ) ), 'No definition/publication/revision/default/profile/role mutation' );
echo 'PASS export application: ' . $checks . " assertions; one read, no persistence or delivery.\n";
