<?php
/** Loopback-only isolated PHP request fixture; never bootstraps WordPress. */
if ( 'cli-server' !== PHP_SAPI || ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) || ! hash_equals( (string) getenv( 'PJH5PWP_UI_TEST_KEY' ), (string) ( $_GET['key'] ?? '' ) ) || '' === (string) getenv( 'PJH5PWP_UI_TEST_KEY' ) ) { http_response_code( 403 ); exit; }
require dirname( __DIR__, 2 ) . '/includes/theme-domain.php';
require dirname( __DIR__, 2 ) . '/includes/administration.php';
require dirname( __DIR__ ) . '/admin/wordpress-doubles.php';
require __DIR__ . '/support.php';
function wp_salt( $scheme ) { return str_repeat( 'fixture-only-secret-', 4 ); }
function get_current_user_id() { return 1; }
function wp_get_session_token() { return $_GET['session'] ?? 'fixture-session'; }
$GLOBALS['pjh5pwp_test_cap'] = ! isset( $_GET['deny'] );
// PHP normally adds WordPress request slashes during bootstrap; emulate only that step.
function fixture_slash( $v ) { return is_array( $v ) ? array_map( 'fixture_slash', $v ) : addslashes( $v ); }
$_POST = fixture_slash( $_POST );
$store = new Transfer_Test_Store();
if ( isset( $_GET['stale'] ) ) { ++$store->state['revision']; }
$factory = static function () use ( $store ) { return new \Papijo\H5PThemerWordPress\Admin\Transfer_Admin( $store ); };
$controller = new \Papijo\H5PThemerWordPress\Admin\Admin_Controller( static function () use ( $store ) { return new \Papijo\H5PThemerWordPress\Admin\Admin_Service( $store ); }, null, $factory );
register_shutdown_function( static function () use ( $store ) { if ( ! headers_sent() ) { header( 'X-Fixture-Writes: ' . $store->writes ); } } );
try { $controller->load(); $controller->render(); }
catch ( Pjh5pwp_Test_Redirect $redirect ) {
	header( 'X-Fixture-Writes: ' . $store->writes ); header( 'Location: ' . $GLOBALS['pjh5pwp_test_redirect'][0], true, $GLOBALS['pjh5pwp_test_redirect'][1] );
} catch ( RuntimeException $error ) { http_response_code( 403 ); echo 'Fixture access denied'; }
