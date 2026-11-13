<?php
/** Loopback-only HTTP fixture. Never boot WordPress or connect to a database. */
if ( 'cli-server' !== PHP_SAPI || ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) { exit; }
$wp_source = getenv( 'PJH5PWP_TEST_WP_SOURCE' );
if ( ! $wp_source || ! is_file( $wp_source . '/wp-includes/pluggable.php' ) ) { http_response_code( 500 ); exit; }
define( 'ABSPATH', $wp_source . '/' ); define( 'WPINC', 'wp-includes' ); define( 'HOUR_IN_SECONDS', 3600 ); define( 'DAY_IN_SECONDS', 86400 );
define( 'LOGGED_IN_COOKIE', 'pjh5pwp_disposable_session' );
foreach ( array( 'AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE' ) as $scheme ) {
	define( $scheme . '_KEY', 'disposable-fixture-key-' . $scheme ); define( $scheme . '_SALT', 'disposable-fixture-salt-' . $scheme );
}
require $wp_source . '/wp-includes/compat.php';
require $wp_source . '/wp-includes/plugin.php';
require $wp_source . '/wp-includes/class-wp-session-tokens.php';
require $wp_source . '/wp-includes/class-wp-user-meta-session-tokens.php';

// Only user and metadata storage are doubles; cookie HMAC/parser/session verification is real WordPress.
function __( $text ) { return $text; }
function get_userdata( $id ) { return in_array( (int) $id, array( 1, 2, 3 ), true ) ? (object) array( 'ID' => (int) $id, 'user_login' => 'fixture' . $id, 'user_pass' => 'fixture-password-hash' ) : false; }
function get_user_by( $field, $value ) { return 'login' === $field && preg_match( '/\Afixture([123])\z/', $value, $m ) ? get_userdata( (int) $m[1] ) : false; }
function get_user_meta( $id, $key, $single ) { return 'session_tokens' === $key ? array( hash( 'sha256', 'fixture-session-' . $id ) => array( 'expiration' => time() + 3600 ) ) : array(); }
function update_user_meta() { throw new RuntimeException( 'No writes' ); }
function update_site_option() { throw new RuntimeException( 'No writes' ); }
function wp_doing_ajax() { return true; }
function is_ssl() { return false; }
require $wp_source . '/wp-includes/pluggable.php';
function get_current_user_id() { return (int) wp_validate_auth_cookie( $_COOKIE[ LOGGED_IN_COOKIE ] ?? '', 'logged_in' ); }
function wp_get_current_user() { return (object) array( 'ID' => get_current_user_id() ); }
function wp_get_session_token() { $cookie=wp_parse_auth_cookie('','logged_in');return $cookie['token']??''; }
function current_user_can( $cap ) { $id=get_current_user_id();return in_array($id,array(1,2),true) && ('view_h5p_contents'===$cap || (1===$id && 'view_others_h5p_contents'===$cap)); }
function home_url( $path = '/' ) { return 'http://' . $_SERVER['HTTP_HOST'] . $path; }
function admin_url() { return home_url( '/wp-admin/' ); }
function nocache_headers() { header( 'Cache-Control: no-cache' ); }
function wp_send_json( $data, $status = 200 ) { http_response_code( $status ); header( 'Content-Type: application/json; charset=UTF-8' ); echo json_encode( $data ); exit; }
require dirname( __DIR__, 2 ) . '/includes/runtime-wordpress.php';
$h5p_source=getenv('PJH5PWP_TEST_H5P_SOURCE');
if($h5p_source) {
	define('WP_PLUGIN_DIR',dirname($h5p_source));
	require_once $wp_source.'/wp-includes/formatting.php';
	require $h5p_source.'/autoloader.php';
	foreach(array('H5PCore','H5P_Plugin','H5P_Plugin_Admin','H5PContentAdmin') as $class){class_exists($class);}
	$GLOBALS['wpdb']=new class {
		public $prefix='fixture_';public $last_error='';
		public function prepare($sql,$id){if(strpos($sql,'SELECT id, user_id FROM fixture_h5p_contents WHERE id = %d LIMIT 1')!==0){throw new RuntimeException('Read-only identity SQL required');}return (string)$id;}
		public function get_row($id){return in_array($id,array('10','11'),true)?(object)array('id'=>$id,'user_id'=>'10'===$id?2:1):null;}
	};
	if(preg_match('~\A/admin-grant/([1-9][0-9]{0,9})\z~',$_SERVER['REQUEST_URI'],$m)) {
		if(!\Papijo\H5PThemerWordPress\Runtime\WordPress_Context::admin_content_allowed($m[1])){wp_send_json(array('error'=>'forbidden'),403);}
		wp_send_json(array('nonce'=>wp_create_nonce(\Papijo\H5PThemerWordPress\Runtime\WordPress_Context::admin_nonce_action($m[1]))));
	}
}

// Fixture-only login route issues fixed disposable sessions; it is never production code.
if ( preg_match( '~\A/login/([123])(?:/(revoked|expired))?\z~', $_SERVER['REQUEST_URI'], $m ) ) {
	$mode = $m[2] ?? '';
	setcookie( LOGGED_IN_COOKIE, wp_generate_auth_cookie( (int) $m[1], time() + ( 'expired' === $mode ? -7200 : 3600 ), 'logged_in', 'revoked' === $mode ? 'revoked-session' : 'fixture-session-' . $m[1] ), 0, '/' );
	wp_send_json( array( 'fixture' => true ) );
}
$service = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Service(
	static function () { return array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() ); },
	static function ( $id ) {
		$theme = in_array( $id, array( 1, 2 ), true ) ? array( 'kind' => 'builtin', 'id' => 1 === $id ? 'lavender' : 'mint', 'version' => \Papijo\H5PThemerWordPress\Theme\Preset_Catalog::VERSION ) : null;
		return array( 'theme' => ( new \Papijo\H5PThemerWordPress\Theme\Palette_Resolver() )->resolve( $theme ) + array( 'source' => $theme ? 'personal' : 'global' ), 'density' => 1 === $id ? 'large' : ( 2 === $id ? 'small' : 'original' ) );
	} );
$endpoint = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint( $service, 'get_current_user_id' );
$endpoint->register();
// Simulate pre-existing headers from a CMS/plugin, then the real endpoint must remove them.
header( 'Access-Control-Allow-Origin: *' ); header( 'Access-Control-Allow-Credentials: true' );
header( 'ETag: "fixture-stale"' ); header( 'Last-Modified: Sat, 01 Jan 2000 00:00:00 GMT' );
do_action( 'init' );
// Actual WordPress CORS routine, including its early OPTIONS exit.
require $wp_source . '/wp-includes/http.php';
send_origin_headers();
do_action( get_current_user_id() ? 'wp_ajax_pjh5pwp_appearance' : 'wp_ajax_nopriv_pjh5pwp_appearance' );
throw new RuntimeException( 'Endpoint failed to terminate' );
