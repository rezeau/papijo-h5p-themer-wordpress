<?php
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL ); set_error_handler( static function ( $n, $m, $f, $l ) { throw new ErrorException( $m, 0, $n, $f, $l ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-domain.php';
require dirname( __DIR__, 2 ) . '/includes/administration.php';
require dirname( __DIR__ ) . '/admin/wordpress-doubles.php';
require __DIR__ . '/support.php';
use Papijo\H5PThemerWordPress\Admin\Import_Review;
use Papijo\H5PThemerWordPress\Admin\Transfer_Upload;
use Papijo\H5PThemerWordPress\Admin\Transfer_Admin;
use Papijo\H5PThemerWordPress\Admin\Transfer_View;
use Papijo\H5PThemerWordPress\Admin\Transfer_Download;
use Papijo\H5PThemerWordPress\Admin\Admin_Service;
use Papijo\H5PThemerWordPress\Admin\Admin_View;
use Papijo\H5PThemerWordPress\Admin\Admin_Controller;
$checks = 0; $now = 1800000000; $owner = '1:session-a';
function check( $v, $m ) { global $checks; if ( ! $v ) { throw new RuntimeException( $m ); } ++$checks; }
function good( $r ) { check( empty( $r['errors'] ), 'Expected valid UI action: ' . implode( ',', $r['errors'] ) ); return $r; }
function reject( $r, $code ) { check( array( $code ) === $r['errors'] && ! isset( $r['download'] ) && ! isset( $r['review'] ) && ! isset( $r['summary'] ), 'Expected safe rejection: ' . $code ); }
$reviews = new Import_Review( static function () { return str_repeat( 's', 64 ); }, static function () use ( &$owner ) { return $owner; }, static function () use ( &$now ) { return $now; } );
$file = tempnam( sys_get_temp_dir(), 'pjh5pwp-ui-' ); $json = transfer_json(); file_put_contents( $file, $json );
$uploads = new Transfer_Upload( static function ( $p ) use ( $file ) { return $p === $file; } );
$files = array( 'theme_json' => array( 'error' => UPLOAD_ERR_OK, 'tmp_name' => $file, 'size' => strlen( $json ), 'name' => '../../evil.php', 'type' => 'text/x-php' ) );
$url = 'http://isolated.invalid/wp-admin/admin.php?page=pjh5pwp-themes'; $store = new Transfer_Test_Store();
$factory = static function () use ( $store, $reviews, $uploads ) { return new Transfer_Admin( $store, $reviews, $uploads ); };
try {
	$controller = $factory(); $before = serialize( $store->state );
	$preview = good( $controller->submit( transfer_post( $store, 'import_preview' ), $files ) );
	check( 0 === $store->writes && $before === serialize( $store->state ), 'Preview no persistence or revision/publication mutation' );
	check( count( $preview['plan']['records'] ) === 2 && false === strpos( json_encode( $preview['plan']['records'] ), 'density' ), 'Canonical color preview only' );
	$status = ( new Admin_Service( $store ) )->status(); $view = new Transfer_View( $url );
	$preview_html = $view->import_page( $status, $preview );
	foreach ( array( 'ocean', 'second', 'moodle-478fed30d2a4', 'Color overrides', 'Confirm import', 'unpublished', '15 minutes' ) as $needle ) { check( false !== strpos( $preview_html, $needle ), 'Preview includes ' . $needle ); }
	check( false === strpos( $preview_html, '<script>' ) && false !== strpos( $preview_html, '&lt;script&gt;' ), 'Preview names/descriptions escaped' );
	check( false !== strpos( $preview_html, 'name="confirm" value="1" required' ), 'Explicit keyboard-accessible confirmation' );
	$commit = transfer_commit( $preview['review'] ); $no = $commit; unset( $no['confirm'] ); reject( $factory()->submit( $no, array() ), 'import_confirmation_required' );
	$no = $commit; $no['nonce'] = 'bad'; $reads = $store->reads; reject( $factory()->submit( $no, array() ), 'invalid_nonce' ); check( $reads === $store->reads, 'Bad commit nonce before repository access' );
	foreach ( array( 'payload', 'revision', 'context', 'digest', 'signature', 'expires' ) as $key ) { $bad = $commit; $bad['review'][$key] .= '0'; reject( $factory()->submit( $bad, array() ), 'invalid_review' ); }
	$owner = '2:other-session'; reject( $factory()->submit( $commit, array() ), 'invalid_review' ); $owner = '1:session-a';
	$owner = '1:logged-out-and-in'; reject( $factory()->submit( $commit, array() ), 'invalid_review' ); $owner = '1:session-a';
	$now += 901; reject( $factory()->submit( $commit, array() ), 'review_expired' ); $now -= 901;
	$result = good( $factory()->submit( $commit, array() ) ); check( 2 === $result['summary']['count'] && 1 === $store->writes, 'Reconstructed review commits one complete bundle across instances' );
	check( false === $store->state['publication']['themes']['custom:ocean'] && false === $store->state['publication']['themes']['custom:second'], 'Both imports unpublished' );
	check( $store->state['settings'] === unserialize( $before )['settings'] && $store->state['definitions']['existing'] === unserialize( $before )['definitions']['existing'], 'Default and existing legacy Density preserved' );
	reject( $factory()->submit( $commit, array() ), 'destination_unavailable' );
	$single = good( $factory()->submit( transfer_post( $store, 'export', array( 'mode' => 'single', 'id' => 'ocean' ) ), array() ) );
	check( $single['download']['filename'] === 'papijo-h5p-theme-colors-ocean.json' && false === strpos( $single['download']['json'], '"density"' ), 'Single safe colors-only download' );
	$selected = good( $factory()->submit( transfer_post( $store, 'export', array( 'mode' => 'selected', 'ids' => array( 'second', 'ocean' ) ) ), array() ) );
	$all = good( $factory()->submit( transfer_post( $store, 'export', array( 'mode' => 'all' ) ), array() ) ); check( count( json_decode( $all['download']['json'], true )['themes'] ) === 3, 'All custom themes, including hidden' );
	$headers = array(); $body = ''; Transfer_Download::send( $single['download'], static function ( $h ) use ( &$headers ) { $headers[] = $h; }, static function ( $s ) use ( &$body ) { $body = $s; } );
	foreach ( array( 'Content-Type: application/json; charset=UTF-8', 'Content-Disposition: attachment; filename="papijo-h5p-theme-colors-ocean.json"', 'Cache-Control: private, no-store', 'X-Content-Type-Options: nosniff', 'Content-Length: ' . strlen( $body ) ) as $h ) { check( in_array( $h, $headers, true ), 'Safe download header: ' . $h ); }
	check( $body === $single['download']['json'], 'Exact body, no appended UI' );
	$bad = $single['download']; $bad['filename'] = "x.json\r\nBad: header"; try { Transfer_Download::send( $bad ); throw new LogicException( 'Expected rejection' ); } catch ( RuntimeException $e ) { check( 'invalid_download' === $e->getMessage(), 'Header injection rejected' ); }
	reject( $factory()->submit( transfer_post( $store, 'export', array( 'mode' => 'single', 'id' => 'builtin:mint' ) ), array() ), 'invalid_export_selection' );
	$GLOBALS['pjh5pwp_test_cap'] = false; $reads = $store->reads; reject( $factory()->submit( $commit, array() ), 'forbidden' ); reject( $factory()->submit( transfer_post( $store, 'export', array( 'mode' => 'all' ) ), array() ), 'forbidden' ); check( $store->reads === $reads, 'Unauthorized no reads' ); $GLOBALS['pjh5pwp_test_cap'] = true;
	$GLOBALS['pjh5pwp_test_multisite'] = true; reject( $factory()->submit( $commit, array() ), 'forbidden' ); $GLOBALS['pjh5pwp_test_multisite'] = false;
	$post = transfer_post( $store, 'import_preview' ); $post['nonce'] = 'invalid'; reject( $factory()->submit( $post, $files ), 'invalid_nonce' );
	foreach ( array( array(), array( 'theme_json' => array( 'error' => UPLOAD_ERR_NO_FILE ) ) ) as $missing ) { reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $missing ), 'upload_missing' ); }
	foreach ( array( UPLOAD_ERR_PARTIAL, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION ) as $err ) { $bad = $files; $bad['theme_json']['error'] = $err; reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'upload_failed' ); }
	foreach ( array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ) as $err ) { $bad = $files; $bad['theme_json']['error'] = $err; reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'upload_too_large' ); }
	$bad = $files; $bad['theme_json']['size'] = 1048577; reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'upload_too_large' );
	$bad = $files; $bad['theme_json']['size'] = 0; reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'upload_failed' );
	$bad = $files; $bad['theme_json']['tmp_name'] = '/private/arbitrary'; reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'upload_failed' );
	$raw_invalid = array( '{broken', '{}', '[]', '{"format":"unknown","themes":[]}', '{"format":"papijo-h5p-theme-colors","format_version":2,"themes":[]}' );
	foreach ( $raw_invalid as $raw ) { file_put_contents( $file, $raw ); $bad = $files; $bad['theme_json']['size'] = strlen( $raw ); reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'invalid_import_input' ); }
	$raw = transfer_json( array( transfer_record( 'existing' ) ) ); file_put_contents( $file, $raw ); $bad = $files; $bad['theme_json']['size'] = strlen( $raw ); reject( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ), 'destination_unavailable' );
	$native = transfer_record( 'native' ); $native['_config_name'] = 'h5p_themer.theme.native'; $native['schema_version'] = 2; $native['density'] = 'small'; $native['colors'] = (object) $native['colors']; $raw = json_encode( $native ); file_put_contents( $file, $raw ); $bad = $files; $bad['theme_json']['size'] = strlen( $raw ); $p = good( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ) );
	check( ! array_key_exists( 'density', $p['plan']['records'][0] ), 'Native metadata discarded in UI preview' ); good( $factory()->submit( transfer_commit( $p['review'] ), array() ) );
	$raw = transfer_json( array( transfer_record( 'fresh' ) ) ); file_put_contents( $file, $raw ); $bad = $files; $bad['theme_json']['size'] = strlen( $raw );
	$p = good( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ) ); ++$store->state['revision']; reject( $factory()->submit( transfer_commit( $p['review'] ), array() ), 'storage_conflict' );
	$p = good( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ) ); $store->race = static function ( $s ) { ++$s->state['revision']; }; reject( $factory()->submit( transfer_commit( $p['review'] ), array() ), 'storage_conflict' ); check( ! isset( $store->state['definitions']['fresh'] ), 'CAS race no partial import' );
	foreach ( array( 'not_committed' => 'import_write_failed', 'unknown' => 'import_write_unknown', 'committed' => 'import_committed_cache_failed' ) as $fault => $code ) { $p = good( $factory()->submit( transfer_post( $store, 'import_preview' ), $bad ) ); $store->fault = $fault; $r = $factory()->submit( transfer_commit( $p['review'] ), array() ); reject( $r, $code ); check( $r['outcome'] === $fault && false === strpos( implode( ',', $r['errors'] ), 'private' ), 'Outcome preserved without private diagnostics' ); $store->fault = null; }
	$overview_html = ( new Admin_View( $url ) )->render( ( new Admin_Service( $store ) )->status() );
	foreach ( array( 'Save global default', 'Save learner visibility', 'Edit / rename', 'Duplicate', 'Delete', 'Export colors', 'Export selected colors', 'Export all custom colors', 'Import theme colors' ) as $label ) { check( false !== strpos( $overview_html, $label ), 'Existing/new overview operation: ' . $label ); }
	check( false === strpos( $overview_html, 'pjh5pwp-export-daylight' ), 'No independent builtin export form' );
	// Actual page-load controller boundary, with terminal delivery captured by a trusted seam.
	final class Test_Download_Stop extends LogicException {}
	$c = new Admin_Controller( static function () use ( $store ) { return new Admin_Service( $store ); }, null, $factory, static function ( $d ) { $GLOBALS['download'] = $d; throw new Test_Download_Stop(); } );
	$_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = transfer_post( $store, 'export', array( 'mode' => 'single', 'id' => 'existing' ) ); $_FILES = array();
	try { $c->load(); throw new RuntimeException( 'Missing terminal download' ); } catch ( Test_Download_Stop $e ) { check( isset( $GLOBALS['download']['json'] ), 'Controller routes download before rendering' ); }
	$_POST = array(); $c->load(); $_GET = array( 'pjh5pwp_view' => 'import' ); ob_start(); $c->render(); $empty_html = ob_get_clean(); check( false !== strpos( $empty_html, 'server upload/POST limit' ), 'Truncated PHP POST handled on import page' );
	if ( '--render' === ( $argv[1] ?? null ) ) { echo json_encode( array( 'overview' => $overview_html, 'preview' => $preview_html, 'upload' => $view->import_page( $status, array() ) ) ); }
	else { echo 'PASS transfer administration: ' . $checks . " assertions; isolated request boundaries.\n"; }
} finally { if ( is_file( $file ) ) { unlink( $file ); } }
