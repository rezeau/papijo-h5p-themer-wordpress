<?php
/** Isolated WordPress doubles. Does not load WordPress, connect to a DB or write files. */

if ( 'cli' !== PHP_SAPI ) { exit; }

error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
} );

$GLOBALS['pjh5pwp_scenario'] = isset( $argv[1] ) ? $argv[1] : 'compatible';
$scenario = $GLOBALS['pjh5pwp_scenario'];
define( 'ABSPATH', __DIR__ . '/fixtures/' );
$actual_root = 'actual-integration' === $scenario && isset( $argv[2] ) ? realpath( $argv[2] ) : false;
if ( 'actual-integration' === $scenario && ! $actual_root ) {
	throw new RuntimeException( 'An existing H5P plugin root is required' );
}
define( 'WP_PLUGIN_DIR', $actual_root ? dirname( $actual_root ) : __DIR__ . '/fixtures/' . ( 'missing' === $scenario ? 'absent' : 'plugins' ) );
$GLOBALS['pjh5pwp_options'] = array( 'active_plugins' => array( 'h5p/h5p.php' ), 'pjh5pwp_existing' => 'preserve me' );
$GLOBALS['pjh5pwp_network'] = array();
$GLOBALS['pjh5pwp_multisite'] = false;
$GLOBALS['pjh5pwp_hooks'] = array();
$GLOBALS['pjh5pwp_activations'] = array();
$GLOBALS['pjh5pwp_can_manage'] = true;
$GLOBALS['pjh5pwp_screen'] = 'plugins';
$GLOBALS['pjh5pwp_i18n_ready'] = false;
$GLOBALS['pjh5pwp_i18n_loaded'] = false;
$GLOBALS['pjh5pwp_checks'] = 0;

function pjh5pwp_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$GLOBALS['pjh5pwp_checks'];
}
function get_option( $name, $default = false ) { return isset( $GLOBALS['pjh5pwp_options'][ $name ] ) ? $GLOBALS['pjh5pwp_options'][ $name ] : $default; }
function get_site_option( $name, $default = false ) { return 'active_sitewide_plugins' === $name ? $GLOBALS['pjh5pwp_network'] : $default; }
function is_multisite() { return $GLOBALS['pjh5pwp_multisite']; }
function get_file_data( $path, $headers, $context = '' ) {
	$result = array(); $source = file_get_contents( $path );
	foreach ( $headers as $key => $label ) {
		$result[ $key ] = preg_match( '/^[ \t\/*#@]*' . preg_quote( $label, '/' ) . ':(.*)$/mi', $source, $match ) ? trim( $match[1] ) : '';
	}
	return $result;
}
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_basename( $file ) { return 'papijo-h5p-themer-wordpress/' . basename( $file ); }
function add_action( $hook, $callback, $priority = 10, $arguments = 1 ) { $GLOBALS['pjh5pwp_hooks'][ $hook ][] = array( $callback, $priority, $arguments ); }
function add_filter( $hook, $callback, $priority = 10, $arguments = 1 ) { add_action( $hook, $callback, $priority, $arguments ); }
function has_action( $hook, $callback ) {
	foreach ( isset( $GLOBALS['pjh5pwp_hooks'][ $hook ] ) ? $GLOBALS['pjh5pwp_hooks'][ $hook ] : array() as $entry ) {
		if ( $entry[0] === $callback ) { return $entry[1]; }
	}
	return false;
}
function register_activation_hook( $file, $callback ) { $GLOBALS['pjh5pwp_activations'][ $file ] = $callback; }
function current_user_can( $capability ) { return $GLOBALS['pjh5pwp_can_manage']; }
function get_current_screen() { return $GLOBALS['pjh5pwp_screen'] ? (object) array( 'base' => $GLOBALS['pjh5pwp_screen'] ) : null; }
function __( $text, $domain ) {
	if ( ! $GLOBALS['pjh5pwp_i18n_ready'] ) { throw new RuntimeException( 'Translation called before init' ); }
	pjh5pwp_check( 'papijo-h5p-themer-wordpress' === $domain, 'Translation domain' );
	return $text;
}
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain ) { return esc_html( __( $text, $domain ) ); }
function load_plugin_textdomain( $domain, $deprecated, $path ) {
	pjh5pwp_check( $GLOBALS['pjh5pwp_i18n_ready'], 'Translation loader runs at init' );
	pjh5pwp_check( 'papijo-h5p-themer-wordpress/languages' === $path, 'Translation path' );
	$GLOBALS['pjh5pwp_i18n_loaded'] = true;
}
function update_option() { throw new RuntimeException( 'Database mutation' ); }
function add_option() { throw new RuntimeException( 'Database mutation' ); }
function delete_option() { throw new RuntimeException( 'Database mutation' ); }
function update_user_meta() { throw new RuntimeException( 'User metadata mutation' ); }
function set_transient() { throw new RuntimeException( 'Transient mutation' ); }
function wp_schedule_event() { throw new RuntimeException( 'Cron mutation' ); }
function flush_rewrite_rules() { throw new RuntimeException( 'Rewrite mutation' ); }
function remove_action() { throw new RuntimeException( 'Foreign callback mutation' ); }
function deactivate_plugins() { throw new RuntimeException( 'Plugin deactivation' ); }
function wp_enqueue_style( ...$args ) { $GLOBALS['pjh5pwp_test_assets'][] = array( 'style', $args ); }
function wp_enqueue_script( ...$args ) { $GLOBALS['pjh5pwp_test_assets'][] = array( 'script', $args ); }

if ( in_array( $scenario, array( 'missing', 'inactive' ), true ) ) {
	$GLOBALS['pjh5pwp_options']['active_plugins'] = array();
} elseif ( 'foreign-classes' === $scenario ) {
	require __DIR__ . '/fixtures/foreign-classes.php';
} elseif ( 'autoload-failure' === $scenario ) {
	spl_autoload_register( static function () { throw new RuntimeException( 'Broken third-party autoloader' ); } );
} elseif ( 'active-unloaded' !== $scenario ) {
	if ( $actual_root ) {
		// Registers the actual loader only. h5p.php and service constructors are not run.
		require $actual_root . '/autoloader.php';
	} else {
		spl_autoload_register( static function ( $class ) {
			$map = array( 'H5P_Plugin' => '/public/class-h5p-plugin.php', 'H5PCore' => '/h5p-php-library/h5p.classes.php' );
			if ( isset( $map[ $class ] ) ) { require WP_PLUGIN_DIR . '/h5p' . $map[ $class ]; }
		} );
	}
}
if ( in_array( $scenario, array( 'wrong-api', 'malformed-api' ), true ) ) {
	class_exists( 'H5PCore' );
	H5PCore::$coreApi = array( 'majorVersion' => 1, 'minorVersion' => 'wrong-api' === $scenario ? 29 : '28' );
}
if ( 'snordian-active' === $scenario ) { $GLOBALS['pjh5pwp_options']['active_plugins'][] = 'snordians-h5p-themer/snordians-h5p-themer.php'; }
if ( 'snordian-constant' === $scenario ) { define( 'SNORDIANSH5PTHEMER_VERSION', '0.0.12' ); }
if ( 'snordian-namespace' === $scenario ) { require __DIR__ . '/fixtures/snordian-marker.php'; }
if ( 'snordian-priority-zero' === $scenario ) { add_action( 'h5p_alter_library_scripts', 'Snordian\\H5PThemer\\h5p_themer_alter_scripts', 0, 3 ); }
if ( 'multisite' === $scenario ) {
	$GLOBALS['pjh5pwp_multisite'] = true;
	$GLOBALS['pjh5pwp_options']['active_plugins'] = array();
	$GLOBALS['pjh5pwp_network'] = array( 'h5p/h5p.php' => 1, 'snordians-h5p-themer/snordians-h5p-themer.php' => 2 );
}
$before_options = $GLOBALS['pjh5pwp_options'];
$before_network = $GLOBALS['pjh5pwp_network'];
$before_hooks = $GLOBALS['pjh5pwp_hooks'];
require dirname( __DIR__ ) . '/papijo-h5p-themer-wordpress.php';
use Papijo\H5PThemerWordPress\Plugin;
Plugin::boot();
Plugin::boot();
pjh5pwp_check( empty( $GLOBALS['pjh5pwp_test_assets'] ), 'Registering hooks never enqueues assets during bootstrap' );
$policy = Plugin::instance()->compatibility();
$status = $policy->evaluate();
Plugin::activate(); // Only the isolated handler; never WordPress activate_plugin().
pjh5pwp_check( 1 === count( $GLOBALS['pjh5pwp_activations'] ), 'Activation callback registered once' );
pjh5pwp_check( 3 === count( $GLOBALS['pjh5pwp_hooks']['admin_notices'] ), 'Idempotent compatibility, state and Profile storage diagnostics' );
pjh5pwp_check( $status['runtime_enabled'] === \Papijo\H5PThemerWordPress\Compatibility\Runtime_Policy::configured() && true === $status['runtime_implemented'], 'Implementation and configuration are separate from admission' );
pjh5pwp_check( $before_options === $GLOBALS['pjh5pwp_options'] && $before_network === $GLOBALS['pjh5pwp_network'], 'Configuration preserved' );
$expected = array(
	'missing' => 'h5p_missing', 'inactive' => 'h5p_inactive', 'active-unloaded' => 'h5p_capabilities_unverified',
	'foreign-classes' => 'h5p_capabilities_unverified', 'autoload-failure' => 'h5p_capabilities_unverified',
	'wrong-api' => 'h5p_core_unsupported', 'malformed-api' => 'h5p_core_unsupported',
	'missing-method' => 'h5p_capabilities_unverified', 'missing-hooks' => 'h5p_capabilities_unverified',
	'wrong-reference' => 'h5p_capabilities_unverified',
);
pjh5pwp_check( ( isset( $expected[ $scenario ] ) ? $expected[ $scenario ] : 'h5p_supported' ) === $status['h5p']['code'], 'Dependency classification: ' . $scenario );
if ( 0 === strpos( $scenario, 'snordian-' ) || 'multisite' === $scenario ) {
	pjh5pwp_check( $status['snordian']['active'] && $status['runtime_suspended'], 'SNORDIAN suspends colors and Density' );
	pjh5pwp_check( in_array( 'snordian_active', $status['blockers'], true ), 'Conflict reason' );
} elseif ( ! isset( $expected[ $scenario ] ) ) {
	pjh5pwp_check( ! $status['snordian']['active'] && $status['snordian']['installed'], 'Installed inactive SNORDIAN does not suspend' );
	pjh5pwp_check( $status['future_runtime_permitted'] && ( \Papijo\H5PThemerWordPress\Compatibility\Runtime_Policy::configured() ? 'available' : 'disabled' ) === $status['runtime_state'], 'Supported future admission is separate from implementation' );
}
if ( 'multisite' === $scenario ) { pjh5pwp_check( in_array( 'multisite_deferred', $status['blockers'], true ), 'Multisite is deferred' ); }
if ( 'late-conflict' === $scenario ) {
	$GLOBALS['pjh5pwp_options']['active_plugins'][] = 'snordians-h5p-themer/snordians-h5p-themer.php';
	pjh5pwp_check( ! $policy->evaluate()['future_runtime_permitted'], 'Late conflict revokes admission without cached authority' );
	$GLOBALS['pjh5pwp_options'] = $before_options;
	pjh5pwp_check( $policy->evaluate()['future_runtime_permitted'], 'Later removal is reflected' );
}
foreach ( array_diff( array_keys( $GLOBALS['pjh5pwp_hooks'] ), array_keys( $before_hooks ) ) as $hook ) {
	pjh5pwp_check( in_array( $hook, array( 'plugins_loaded', 'init', 'admin_notices', 'network_admin_notices', 'admin_menu', 'admin_enqueue_scripts', 'show_user_profile', 'edit_user_profile', 'user_profile_update_errors', 'profile_update', 'print_h5p_content', 'wp_ajax_pjh5pwp_appearance', 'wp_ajax_nopriv_pjh5pwp_appearance', 'allowed_http_origins', 'wp_enqueue_scripts', 'wp_footer', 'script_loader_tag', 'admin_post_pjh5pwp_profile_storage_retry', 'admin_post_pjh5pwp_state_storage_retry' ), true ), 'Only foundation/admin/profile, authorized setup retry and read-only runtime transport/observation hooks' );
}
pjh5pwp_check( 2 === $GLOBALS['pjh5pwp_hooks']['print_h5p_content'][0][2], 'Playback filter receives wrapper and main content' );
pjh5pwp_check( $GLOBALS['pjh5pwp_hooks']['wp_ajax_pjh5pwp_appearance'][0][0] === $GLOBALS['pjh5pwp_hooks']['wp_ajax_nopriv_pjh5pwp_appearance'][0][0], 'One identity-derived handler for logged-in and anonymous dispatch' );
foreach ( $before_hooks as $hook => $entries ) { pjh5pwp_check( $entries === $GLOBALS['pjh5pwp_hooks'][ $hook ], 'Foreign callbacks preserved' ); }
$GLOBALS['pjh5pwp_i18n_ready'] = true;
Plugin::instance()->load_translations();
pjh5pwp_check( $GLOBALS['pjh5pwp_i18n_loaded'], 'Translations loaded' );
$notice = $GLOBALS['pjh5pwp_hooks']['admin_notices'][0][0];
ob_start(); call_user_func( $notice ); $html = ob_get_clean();
pjh5pwp_check( false === strpos( $html, 'Phase 1A' ) && false === strpos( $html, 'Playback colors and Density application are not implemented' ), 'No obsolete development notice on Plugins' );
pjh5pwp_check( false === strpos( $html, "SNORDIAN&#039;s H5P Themer" ), 'No SNORDIAN warning on Plugins' );
if ( isset( $expected[ $scenario ] ) || 'multisite' === $scenario ) { pjh5pwp_check( false !== strpos( $html, 'notice-warning' ), 'Other administrator diagnostics preserved on Plugins' ); }
foreach ( array( 'h5p_page_pjh5pwp-themes', 'h5p-content_page_pjh5pwp-themes', 'contenu-h5p_page_pjh5pwp-themes', 'settings_page_pjh5pwp-themes', 'dashboard', 'plugins_page_other', 'h5p-content_page_pjh5pwp-themes-other', null ) as $screen ) {
	$GLOBALS['pjh5pwp_screen'] = $screen;
	ob_start(); call_user_func( $notice ); $screen_html = ob_get_clean();
	$theme_page = in_array( $screen, array( 'h5p_page_pjh5pwp-themes', 'h5p-content_page_pjh5pwp-themes', 'contenu-h5p_page_pjh5pwp-themes', 'settings_page_pjh5pwp-themes' ), true );
	pjh5pwp_check( ( $theme_page && $status['snordian']['active'] ) === ( false !== strpos( $screen_html, "SNORDIAN&#039;s H5P Themer" ) ), 'SNORDIAN warning only on either Themes page route' );
}
$GLOBALS['pjh5pwp_screen'] = 'h5p_page_pjh5pwp-themes';
$GLOBALS['pjh5pwp_can_manage'] = false;
ob_start(); call_user_func( $notice ); $html = ob_get_clean();
pjh5pwp_check( '' === $html, 'No diagnostic disclosure to learners' );
$GLOBALS['pjh5pwp_can_manage'] = true;
$GLOBALS['pjh5pwp_screen'] = null;
ob_start(); call_user_func( $notice ); ob_end_clean();
$header = get_file_data( PJH5PWP_FILE, array( 'name' => 'Plugin Name', 'version' => 'Version', 'domain' => 'Text Domain', 'php' => 'Requires PHP', 'license' => 'License' ) );
pjh5pwp_check( 'Papi Jo H5P Themer for WordPress' === $header['name'] && PJH5PWP_VERSION === $header['version'], 'Plugin header identity/version' );
pjh5pwp_check( 'papijo-h5p-themer-wordpress' === $header['domain'] && '7.4' === $header['php'] && 'GPL-3.0-or-later' === $header['license'], 'Plugin header requirements' );
echo json_encode( array( 'scenario' => $scenario, 'checks' => $GLOBALS['pjh5pwp_checks'], 'status' => $status ) ) . PHP_EOL;
