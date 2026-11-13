<?php
/** Opt-in disposable database tests; NEVER select DB_NAME from wp-config. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { if ( ! ( error_reporting() & $severity ) ) { return false; } throw new ErrorException( $message, 0, $severity, $file, $line ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-import.php';
require dirname( __DIR__ ) . '/storage/wordpress-doubles.php';
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Owned_WPDB;
use Papijo\H5PThemerWordPress\Storage\WordPress_Connections;
use Papijo\H5PThemerWordPress\Transfer\Theme_JSON;
use Papijo\H5PThemerWordPress\Transfer\Import_Service;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;

function import_credentials( $path ) {
	$tokens = token_get_all( file_get_contents( $path ) ); $config = array();
	$decode = static function ( $s ) { $v = substr( $s, 1, -1 ); return "'" === $s[0] ? str_replace( array( "\\'", "\\\\" ), array( "'", "\\" ), $v ) : stripcslashes( $v ); };
	for ( $i = 0; $i < count( $tokens ); ++$i ) {
		if ( is_array( $tokens[$i] ) && T_STRING === $tokens[$i][0] && 'define' === strtolower( $tokens[$i][1] ) ) {
			$values = array();
			for ( $j = $i + 1; $j < count( $tokens ) && ')' !== $tokens[$j]; ++$j ) { if ( is_array( $tokens[$j] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[$j][0] ) { $values[] = $decode( $tokens[$j][1] ); } }
			if ( 2 === count( $values ) && in_array( $values[0], array( 'DB_HOST', 'DB_USER', 'DB_PASSWORD' ), true ) ) { $config[$values[0]] = $values[1]; }
		}
	}
	if ( 3 !== count( $config ) ) { throw new RuntimeException( 'Literal disposable-server credentials unavailable' ); }
	return $config;
}
function import_bootstrap( array $config, $root ) {
	if ( ! preg_match( '/\Apjh5pwp_import_test_[a-f0-9]{16}\z/', $config['database'] ) ) { throw new RuntimeException( 'Unsafe disposable database name' ); }
	foreach ( array( 'DB_HOST' => $config['DB_HOST'], 'DB_USER' => $config['DB_USER'], 'DB_PASSWORD' => $config['DB_PASSWORD'], 'DB_NAME' => $config['database'], 'DB_CHARSET' => 'utf8mb4', 'DB_COLLATE' => '', 'WP_DEBUG' => false, 'WP_DEBUG_DISPLAY' => false ) as $key => $value ) { define( $key, $value ); }
	require $root . '/wp-includes/class-wpdb.php';
	$GLOBALS['wpdb'] = new \wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST ); $GLOBALS['wpdb']->suppress_errors( true ); $GLOBALS['wpdb']->set_prefix( 'pjh5pwp_' );
	if ( ! $GLOBALS['wpdb']->ready ) { throw new RuntimeException( 'Disposable connection unavailable' ); }
}
function mysql_record( $id ) { return array( 'id' => $id, 'label' => 'Été <theme>', 'base_preset' => array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ), 'colors' => array( '--h5p-theme-background' => '#aBc' ) ); }
function mysql_wire( array $records ) { $r = ( new Theme_JSON() )->serialize( $records ); if ( $r['errors'] ) { throw new RuntimeException( 'Invalid test wire' ); } return $r['data']; }
function mysql_ok( $result ) { if ( $result['errors'] ) { throw new RuntimeException( 'Unexpected import result: ' . implode( ',', $result['errors'] ) ); } return $result['data']; }
$checks = 0;
function mysql_check( $condition, $message ) { global $checks; if ( ! $condition ) { throw new RuntimeException( $message ); } ++$checks; }
final class Import_Barrier {
	private $db;
	public function __construct( $db ) { $this->db = $db; }
	public function __call( $name, $args ) { return call_user_func_array( array( $this->db, $name ), $args ); }
	public function __get( $name ) { return $this->db->$name; }
	public function __isset( $name ) { return isset( $this->db->$name ); }
	public function query( $sql ) {
		++$GLOBALS['import_sql_writes']; fwrite( STDOUT, "READY\n" ); fflush( STDOUT );
		if ( "GO\n" !== fgets( STDIN ) ) { throw new RuntimeException( 'Barrier interrupted' ); }
		return $this->db->query( $sql );
	}
}
if ( '--worker' === ( $argv[1] ?? null ) ) {
	$job = json_decode( fgets( STDIN ), true ); import_bootstrap( $job['config'], $job['root'] ); $GLOBALS['import_sql_writes'] = 0;
	$factory = new WordPress_Connections();
	$store = new WordPress_Snapshot_Store( static function () use ( $factory ) { list( $db, $table ) = $factory->open(); return array( new Import_Barrier( $db ), $table ); } );
	$service = new Import_Service( $store, static function () { return true; } ); $plan = mysql_ok( $service->prepare( mysql_wire( $job['records'] ) ) );
	$result = $service->commit( $plan ); echo json_encode( array( 'result' => $result, 'sql_writes' => $GLOBALS['import_sql_writes'] ) ); $GLOBALS['wpdb']->close(); exit;
}
function race_imports( $config, $root, $round, $overlap ) {
	$processes = array(); $groups = array();
	foreach ( array( 'a', 'b' ) as $tag ) {
		$ids = array( 'race_' . $round . '_' . ( $overlap ? 'shared' : $tag ), 'race_' . $round . '_' . $tag . '_tail' ); $records = array_map( 'mysql_record', $ids ); $groups[] = $ids;
		$p = proc_open( array( PHP_BINARY, __FILE__, '--worker' ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		if ( ! is_resource( $p ) ) { throw new RuntimeException( 'Cannot start disposable worker' ); }
		fwrite( $pipes[0], json_encode( array( 'config' => $config, 'root' => $root, 'records' => $records ) ) . "\n" ); fflush( $pipes[0] ); $processes[] = array( $p, $pipes );
	}
	foreach ( $processes as $entry ) { if ( "READY\n" !== fgets( $entry[1][1] ) ) { throw new RuntimeException( 'Worker did not reach SQL barrier' ); } }
	foreach ( $processes as $entry ) { fwrite( $entry[1][0], "GO\n" ); fclose( $entry[1][0] ); }
	$results = array(); foreach ( $processes as $entry ) { $out = stream_get_contents( $entry[1][1] ); $err = stream_get_contents( $entry[1][2] ); fclose( $entry[1][1] ); fclose( $entry[1][2] ); $exit = proc_close( $entry[0] ); if ( $exit || '' !== $err ) { throw new RuntimeException( 'Disposable worker failed' ); } $results[] = json_decode( $out, true ); }
	return array( $groups, $results );
}
if ( ! isset( $argv[1], $argv[2] ) ) { fwrite( STDERR, "Usage: php tests/import/mysql.php <WordPress source root> <literal credential source>\n" ); exit( 2 ); }
$root = rtrim( $argv[1], '/\\' ); $config = import_credentials( $argv[2] ); $database = 'pjh5pwp_import_test_' . bin2hex( random_bytes( 8 ) ); $config['database'] = $database; $created = false; $admin = null;
try {
	mysqli_report( MYSQLI_REPORT_OFF ); $admin = @new mysqli( $config['DB_HOST'], $config['DB_USER'], $config['DB_PASSWORD'] );
	if ( $admin->connect_errno ) { throw new RuntimeException( 'Disposable server unavailable' ); }
	if ( ! $admin->query( "CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" ) ) { throw new RuntimeException( 'Disposable database creation failed' ); } $created = true;
	if ( ! $admin->select_db( $database ) ) { throw new RuntimeException( 'Disposable database selection failed' ); }
	mysql_check( $admin->query( "CREATE TABLE pjh5pwp_options (option_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, option_name VARCHAR(191) NOT NULL, option_value LONGTEXT NOT NULL, autoload VARCHAR(20) NOT NULL DEFAULT 'yes', PRIMARY KEY(option_id), UNIQUE KEY option_name(option_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" ), 'Disposable options table' );
	import_bootstrap( $config, $root ); $db = $GLOBALS['wpdb']; $store = new WordPress_Snapshot_Store();
	$state = Schema::initial(); $existing = mysql_record( 'existing' ); $existing['schema_version'] = 2; $existing['density'] = 'small'; $state['definitions']['existing'] = $existing; $state['publication']['themes']['custom:existing'] = false; $state['settings']['default_theme'] = array( 'kind' => 'custom', 'id' => 'existing' );
	mysql_check( $store->compare_and_swap( null, $state ), 'Seed existing-schema state in disposable DB' );
	$db->query( "INSERT INTO pjh5pwp_options(option_name,option_value) VALUES ('profile_sentinel','Theme=existing;Density=medium'),('pjh5pwp_role_permissions','unchanged')" );
	$service = new Import_Service( $store, static function () { return true; } ); $wire = mysql_wire( array( mysql_record( 'first' ), mysql_record( 'second' ) ) );
	$plan = mysql_ok( $service->prepare( $wire ) ); $before = $store->read(); mysql_ok( $service->commit( $plan ) ); $after = $store->read();
	mysql_check( $after['revision'] === $before['revision'] + 1 && isset( $after['definitions']['first'], $after['definitions']['second'] ), 'Whole two-member batch and one revision' );
	mysql_check( false === $after['publication']['themes']['custom:first'] && false === $after['publication']['themes']['custom:second'], 'Both customs initially unpublished' );
	mysql_check( $existing === $after['definitions']['existing'] && $before['settings'] === $after['settings'], 'Exact existing definitions/default preserved' );
	mysql_check( $wire === mysql_ok( ( new Theme_JSON() )->export( $after, array( 'first', 'second' ) ) ), 'Colors-only export unchanged after real persistence' );
	$native = mysql_record( 'native' ); $native['schema_version'] = 2; $native['_config_name'] = 'h5p_themer.theme.native'; $native['density'] = 'small'; $native['colors'] = (object) $native['colors'];
	mysql_ok( $service->commit( mysql_ok( $service->prepare( json_encode( $native ) ) ) ) );
	mysql_check( 'large' === $store->read()['definitions']['native']['density'], 'Established default, never incoming native Density' );
	$before = $store->read(); $r = $service->prepare( $wire ); mysql_check( array( 'destination_unavailable' ) === $r['errors'] && $before === $store->read(), 'Existing collisions cannot overwrite' );
	$stale = mysql_ok( $service->prepare( mysql_wire( array( mysql_record( 'stale' ) ) ) ) ); $other = new Import_Service( $store, static function () { return true; } ); mysql_ok( $other->commit( mysql_ok( $other->prepare( mysql_wire( array( mysql_record( 'ahead' ) ) ) ) ) ) );
	mysql_check( array( 'storage_conflict' ) === $service->commit( $stale )['errors'] && ! isset( $store->read()['definitions']['stale'] ), 'Real stale revision rejected without writes' );
	// A CHECK depending on the final member rejects the single aggregate statement.
	mysql_check( $db->query( "ALTER TABLE pjh5pwp_options ADD CONSTRAINT fail_tail CHECK (LOCATE('mid_last', option_value)=0)" ) !== false, 'Install disposable statement-failure fixture' );
	$before = $store->read(); $r = $service->commit( mysql_ok( $service->prepare( mysql_wire( array( mysql_record( 'mid_first' ), mysql_record( 'mid_last' ) ) ) ) ) );
	mysql_check( array( 'import_write_failed' ) === $r['errors'] && 'not_committed' === $r['outcome'], 'Real SQL final-member failure is known no-commit' );
	mysql_check( $before === $store->read() && ! isset( $store->read()['definitions']['mid_first'] ), 'InnoDB statement rollback leaves no first-member partial import' );
	$db->query( 'ALTER TABLE pjh5pwp_options DROP CHECK fail_tail' );
	$readonly = new WordPress_Snapshot_Store( static function () { $connection = new Owned_WPDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST ); $connection->suppress_errors( true ); $connection->query( 'SET SESSION transaction_read_only = 1' ); return array( $connection, 'pjh5pwp_options' ); } );
	$denied = new Import_Service( $readonly, static function () { return true; } ); $before = $store->read(); $r = $denied->commit( mysql_ok( $denied->prepare( mysql_wire( array( mysql_record( 'denied_a' ), mysql_record( 'denied_b' ) ) ) ) ) );
	mysql_check( array( 'import_write_failed' ) === $r['errors'] && $before === $store->read(), 'Real read-only-session failure preserves complete aggregate' );
	for ( $round = 0; $round < 4; ++$round ) {
		$before = $store->read(); list( $groups, $results ) = race_imports( $config, $root, $round, 0 === $round % 2 );
		mysql_check( array( 1, 1 ) === array_column( $results, 'sql_writes' ), 'Both processes reach actual CAS statement after internal read' );
		$wins = array_keys( array_filter( $results, static function ( $r ) { return 'committed' === $r['result']['outcome']; } ) ); mysql_check( 1 === count( $wins ), 'Exactly one two-member import wins' );
		$winner = $wins[0]; $loser = 1 - $winner; $after = $store->read();
		mysql_check( $before['revision'] + 1 === $after['revision'], 'Only one revision committed in real race' );
		mysql_check( isset( $after['definitions'][$groups[$winner][0]], $after['definitions'][$groups[$winner][1]] ) && ! isset( $after['definitions'][$groups[$loser][1]] ), 'Whole winner present and loser tail absent' );
		mysql_check( array( 'storage_conflict' ) === $results[$loser]['result']['errors'] && 'not_committed' === $results[$loser]['result']['outcome'], 'Losing process reports CAS conflict' );
	}
	// Import owns its single-statement transaction and cannot commit ambient work.
	$db->query( 'START TRANSACTION' ); $db->query( "UPDATE pjh5pwp_options SET option_value='pending' WHERE option_name='profile_sentinel'" );
	mysql_ok( $service->commit( mysql_ok( $service->prepare( mysql_wire( array( mysql_record( 'ambient' ) ) ) ) ) ) ); $db->query( 'ROLLBACK' );
	mysql_check( 'Theme=existing;Density=medium' === $db->get_var( "SELECT option_value FROM pjh5pwp_options WHERE option_name='profile_sentinel'" ) && isset( $store->read()['definitions']['ambient'] ), 'Ambient profile rollback independent of successful import' );
	mysql_check( 'unchanged' === $db->get_var( "SELECT option_value FROM pjh5pwp_options WHERE option_name='pjh5pwp_role_permissions'" ), 'Role state preserved' );
	mysql_check( $existing === $store->read()['definitions']['existing'] && $state['settings'] === $store->read()['settings'], 'Default and existing theme preserved through failures/races' );
	mysql_check( serialize( $store->read() ) === $db->get_var( $db->prepare( 'SELECT option_value FROM pjh5pwp_options WHERE option_name=%s', Schema::OPTION ) ), 'Native serialization matches actual committed row' );
	if ( '--admin-ui' === ( $argv[3] ?? null ) ) { require dirname( __DIR__ ) . '/admin-transfer/mysql-checks.php'; }
	echo 'PASS disposable import MySQL ' . $admin->server_info . ': ' . $checks . " assertions; four synchronized two-process races; real InnoDB statement failures; no site bootstrap.\n"; $db->close();
} finally {
	if ( $created && $admin instanceof mysqli ) {
		if ( ! preg_match( '/\Apjh5pwp_import_test_[a-f0-9]{16}\z/', $database ) || $database !== $config['database'] ) { throw new RuntimeException( 'Unsafe disposable cleanup refused' ); }
		if ( ! $admin->query( "DROP DATABASE `$database`" ) ) { throw new RuntimeException( 'Disposable cleanup failed' ); } echo "Disposable import database removed.\n";
	}
	if ( $admin instanceof mysqli ) { try { $admin->close(); } catch ( \Throwable $ignored ) { /* Failed connect already closed the handle. */ } }
}
