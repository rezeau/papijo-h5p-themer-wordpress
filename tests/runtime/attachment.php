<?php
/** Disposable attachment/HTML fixtures. No WordPress bootstrap, sessions or database. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__, 2 ) . '/includes/Compatibility/class-runtime-policy.php';
require dirname( __DIR__, 2 ) . '/includes/runtime-wordpress.php';
use Papijo\H5PThemerWordPress\Runtime\Theme_Attachment;
use Papijo\H5PThemerWordPress\Runtime\Playback_Registry;
use Papijo\H5PThemerWordPress\Runtime\Runtime_Admission;
define( 'PJH5PWP_FILE', dirname( __DIR__, 2 ) . '/papijo-h5p-themer-wordpress.php' );
define( 'PJH5PWP_VERSION', 'fixture' );
$hooks = array(); $assets = array(); $checks = 0; $admin_origin = 'https://fixture.test';
function same( $expected, $actual, $message ) { global $checks; ++$checks; if ( $expected !== $actual ) { throw new RuntimeException( $message ); } }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { global $hooks; $hooks[ $hook ][] = array( $callback, $priority, $args ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function admin_url( $path ) { global $admin_origin; return $admin_origin . '/wp-admin/' . $path; }
function home_url( $path ) { return 'https://fixture.test' . $path; }
function plugins_url( $path, $file ) { return 'https://fixture.test/wp-content/plugins/papijo-h5p-themer-wordpress/' . $path; }
function wp_enqueue_script( ...$args ) { global $assets; $assets[] = $args; }
function wp_enqueue_style( ...$args ) { $GLOBALS['readiness_styles'][] = $args; }
function esc_html__($text, $domain) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr__($text, $domain) { return esc_attr($text); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
class H5PCore { public static function determineEmbedType( $embed, $allowed ) { return $embed; } }
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
$context = true; $enabled = true;
$gate = new Runtime_Admission( static function () use ( &$policy ) { return $policy; } );
$registry = new Playback_Registry( $gate, static function () use ( &$context ) { return $context; } );
$content = array( 'id' => 10, 'embedType' => 'div', 'library' => array( 'id' => 2, 'name' => 'H5P.Accordion', 'majorVersion' => 1, 'minorVersion' => 0, 'embedTypes' => 'div' ) );
$html = '<div class="h5p-content" data-content-id="10"></div>';
$registry->observe( $html, $content );
$dormant = new Theme_Attachment( $registry, static function () { return false; } ); $dormant->register(); $dormant->enqueue();
same( array(), $hooks, 'Opt-out has no attachment hooks' ); same( array(), $assets, 'Opt-out has no assets' );
same( null, $dormant->manifest(), 'Opt-out has no public metadata' );
$attachment = new Theme_Attachment( $registry, static function () use ( &$enabled ) { return $enabled; } );
$attachment->register();
same( array( 'wp_enqueue_scripts', 'wp_footer', 'admin_enqueue_scripts', 'admin_footer', 'print_h5p_content', 'script_loader_tag' ), array_keys( $hooks ), 'Shared controller plus narrowly gated admin attachment hooks' );
same( 5, $hooks['wp_enqueue_scripts'][0][1], 'Enqueue before head asset printers' );
same( 2, $hooks['script_loader_tag'][0][2], 'Tag filter receives handle' );
$attachment->enqueue(); same( 1, count( $assets ), 'Explicitly enabled unique candidate queues one static asset' );
same( array(), $assets[0][2], 'Never an H5P dependency asset' ); same( false, $assets[0][4], 'Synchronous parent head script' );
$manifest = $attachment->manifest();
same( array( 'version', 'endpoint', 'players' ), array_keys( $manifest ), 'Exact public metadata schema' );
same( $registry->candidates(), $manifest['players'], 'Reuse qualified registry' );
$tag = '<script src="https://fixture.test/controller.js"></script>';
$output = $attachment->tag( $tag, Theme_Attachment::HANDLE );
same( true, strpos($output, 'data-pjh5pwp-bootstrap="1"') !== false, 'Head bootstrap contains no premature player manifest' );
ob_start(); $attachment->footer(); $metadata = ob_get_clean();
preg_match('/data-pjh5pwp-manifest="([^"]*)"/', $metadata, $match);
same($manifest, json_decode(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), true), 'Inert footer manifest round trips unchanged');
same('', $attachment->tag('<script defer src="x"></script>', Theme_Attachment::HANDLE), 'Deferred bootstrap rejected');
same('', $attachment->tag('<script async src="x"></script>', Theme_Attachment::HANDLE), 'Async bootstrap rejected');
same(true, strpos($attachment->mark($html, $content), 'data-pjh5pwp-pending="10"') !== false, 'Validated DIV marked before output');
same($html.'foreign', $attachment->mark($html.'foreign', $content), 'Modified markup not marked');
same('assets/css/runtime-readiness.css', substr($GLOBALS['readiness_styles'][0][1], -strlen('assets/css/runtime-readiness.css')), 'Public readiness CSS enqueued');
same( $tag, $attachment->tag( $tag, 'foreign' ), 'Foreign asset untouched' );
same( '', $attachment->tag( $tag . $tag, Theme_Attachment::HANDLE ), 'Ambiguous tag suppressed' );
same( '', $attachment->tag( '<script data-pjh5pwp-theme="forged"></script>', Theme_Attachment::HANDLE ), 'Forged attribute suppressed' );
$context = false; same( null, $attachment->manifest(), 'Admin/editor/external context suppressed at attachment boundary' );
$attachment->enqueue(); same( 1, count( $assets ), 'Excluded context cannot enqueue' ); $context = true;
$policy = array( 'future_runtime_permitted' => false, 'runtime_suspended' => true, 'blockers' => array( 'snordian_active' ) );
same( null, $attachment->manifest(), 'SNORDIAN prevents attachment' );
same( '', $attachment->tag( $tag, Theme_Attachment::HANDLE ), 'Late suspension suppresses queued script tag' );
$attachment->enqueue(); same( 1, count( $assets ), 'Suspension never enqueues' );
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
$admin_origin = 'https://other.test'; same( null, $attachment->manifest(), 'Split-origin endpoint suppressed' ); $admin_origin = 'https://fixture.test';
$enabled = false; same( null, $attachment->manifest(), 'Switch rechecked' ); $enabled = true;
$registry->observe( $html, $content ); same( null, $attachment->manifest(), 'Late repeated content ID revokes attachment' );
$empty = new Playback_Registry( $gate, static function () { return true; } );
same( null, ( new Theme_Attachment( $empty, static function () { return true; } ) )->manifest(), 'No players no attachment' );
same( null, ( new Theme_Attachment( $empty, static function () { throw new RuntimeException(); } ) )->manifest(), 'Switch failure stays dormant' );
$bounded = new Playback_Registry( $gate, static function () { return true; } );
for ( $i = 1; $i <= 64; ++$i ) {
	$content['id'] = $i;
	$bounded->observe( '<div class="h5p-content" data-content-id="' . $i . '"></div>', $content );
}
$bounded_attachment = new Theme_Attachment( $bounded, static function () { return true; } );
same( 64, count( $bounded_attachment->manifest()['players'] ), 'Bounded manifest supports 64 distinct players' );
$content['id'] = 65; $bounded->observe( '<div class="h5p-content" data-content-id="65"></div>', $content );
same( null, $bounded_attachment->manifest(), 'Over-limit page fails closed without expanding observers' );
same( null, ( new Theme_Attachment( $bounded, static function () { return 'true'; } ) )->manifest(), 'Truthy non-boolean switch never enables' );
echo 'Phase 2B-2C attachment: ' . $checks . " assertions passed; source-only fixtures.\n";
