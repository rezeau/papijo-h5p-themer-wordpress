<?php
/** Endpoint/qualification tests; existing real domain/profile/role fixtures, no CMS/DB. */
if ( 'cli' !== PHP_SAPI ) { exit; }
ob_start(); require __DIR__ . '/run.php'; ob_end_clean();
require dirname( __DIR__, 2 ) . '/includes/runtime-wordpress.php';
$checks = 0;

use Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint;
use Papijo\H5PThemerWordPress\Runtime\Appearance_Service;
use Papijo\H5PThemerWordPress\Runtime\Runtime_Admission;
use Papijo\H5PThemerWordPress\Runtime\Playback_Registry;
use Papijo\H5PThemerWordPress\Runtime\WordPress_Context;
use Papijo\H5PThemerWordPress\Storage\Schema;

// WordPress request seams only; preferences and role checks use the existing services.
$admin_context = false; $ajax_context = false; $page_context = true; $preview_context = false;
$customize_context = false; $embed_context = false; $feed_context = false; $hooks = array();
function is_admin() { global $admin_context; return $admin_context; }
function wp_doing_ajax() { global $ajax_context; return $ajax_context; }
function did_action( $hook ) { global $page_context; return 'wp' === $hook && $page_context ? 1 : 0; }
function is_preview() { global $preview_context; return $preview_context; }
function is_customize_preview() { global $customize_context; return $customize_context; }
function is_embed() { global $embed_context; return $embed_context; }
function is_feed() { global $feed_context; return $feed_context; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { global $hooks; $hooks[ $hook ][] = array( $callback, $priority, $args ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function update_option() { throw new RuntimeException( 'No writes' ); }
function update_user_meta() { throw new RuntimeException( 'No writes' ); }
function wp_enqueue_script() { throw new RuntimeException( 'No controller' ); }
function wp_enqueue_style() { throw new RuntimeException( 'No CSS' ); }

$home = 'https://wp-test/'; $admin = 'https://wp-test/wp-admin/';
$server = array( 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'https://wp-test', 'HTTP_REFERER' => 'https://wp-test/lesson/',
	'HTTP_X_PJH5PWP_REQUEST' => 'appearance-v1', 'HTTP_SEC_FETCH_SITE' => 'same-origin',
	'HTTP_SEC_FETCH_MODE' => 'cors', 'HTTP_SEC_FETCH_DEST' => 'empty' );
$post = array( 'action' => Appearance_Endpoint::ACTION, 'request_id' => str_repeat( 'a', 32 ) );
$state = Schema::initial(); $state['settings']['default_theme'] = builtin( 'lavender' );
$registered['subscriber'] = array( 'name' => 'Subscriber', 'capabilities' => array() );
$users[2]->roles = array( 'subscriber' );
$role_store->grants = array( 'subscriber' => true ); $viewer = 0; $viewer_calls = 0;
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array(),
	'runtime_implemented' => false, 'runtime_enabled' => false );
$gate = new Runtime_Admission( static function () use ( &$policy ) { return $policy; } );
$service = new Appearance_Service( static function () use ( $gate ) { return $gate->evaluate( true ); }, array( $profiles, 'effective' ) );
$endpoint = new Appearance_Endpoint( $service, static function () use ( &$viewer, &$viewer_calls ) { ++$viewer_calls; return $viewer; } );
function request_transport( $server_override = array(), $post_override = array(), $get = array() ) {
	global $endpoint, $server, $post, $home, $admin;
	return $endpoint->respond( array_replace( $server, $server_override ), $get, array_replace( $post, $post_override ), $home, $admin );
}
function no_instructions( $response ) {
	$d = $response['body']['appearance'];
	same( false, $d['admission']['permitted'], 'Suspended' ); same( null, $d['theme'], 'No Theme' );
	same( '', $d['color_declarations'], 'No CSS' ); same( null, $d['density'], 'No Density' );
}
$endpoint->register();
same( array( 'wp_ajax_pjh5pwp_appearance', 'wp_ajax_nopriv_pjh5pwp_appearance', 'allowed_http_origins', 'init' ), array_keys( $hooks ), 'Minimal transport hooks only' );
$ajax_context = true; $_POST = $post; $_GET = array(); $_REQUEST = $post;
same( array(), $endpoint->cors_origins( array( 'https://wp-test', '*' ) ), 'No CORS even for a Core-allowed origin' );
$_POST = $_REQUEST = array( 'action' => 'foreign' );
same( array( 'foreign' ), $endpoint->cors_origins( array( 'foreign' ) ), 'Other AJAX CORS untouched' ); $ajax_context = false;

foreach ( array( 0, 3, 1, 2 ) as $viewer ) {
	save_fixture( 'builtin:mint', 'small', $viewer ); $r = request_transport(); $d = $r['body']['appearance'];
	same( 200, $r['status'], 'Anonymous/unauthorized/Administrator/authorized receive safe descriptors' );
	same( in_array( $viewer, array( 1, 2 ), true ) ? 'mint' : 'lavender', $d['theme']['id'], 'Current viewer permission applied' );
	same( in_array( $viewer, array( 1, 2 ), true ) ? 'small' : 'original', $d['density']['selection'], 'Independent Density' );
	same( array( 'version', 'request_id', 'refresh', 'appearance' ), array_keys( $r['body'] ), 'Minimal versioned envelope' );
	same( array( 'admission', 'theme', 'color_declarations', 'density' ), array_keys( $d ), 'Original descriptor only' );
	same( array( 'source', 'kind', 'id', 'version' ), array_keys( $d['theme'] ), 'No private metadata or catalog' );
	same( $r, request_transport(), 'Deterministic serialization' );
}
$viewer = 2;
$before_storage = serialize( array( $state, $profile_store->values, $role_store->grants ) );
foreach ( array( array( 'core', 'original', 'core' ), array( 'builtin:lavender', 'large', 'builtin' ),
	array( 'builtin:mint', 'small', 'builtin' ), array( 'site_default', 'original', 'builtin' ), array( 'custom:deleted', 'large', 'builtin' ) ) as $example ) {
	save_fixture( $example[0], $example[1] ); $before_storage = serialize( array( $state, $profile_store->values, $role_store->grants ) );
	$r = request_transport(); $d = $r['body']['appearance'];
	same( $example[2], $d['theme']['kind'], 'Example Theme/fallback' ); same( $example[1], $d['density']['selection'], 'Example Density' );
	same( $service->describe( $viewer ), $d, 'Reuses existing descriptor/serializer exactly' );
	same( $before_storage, serialize( array( $state, $profile_store->values, $role_store->grants ) ), 'No preference/library writes' );
}
save_fixture( 'builtin:mint', 'small' ); $viewer = 2;
same( 'mint', request_transport()['body']['appearance']['theme']['id'], 'Logged in' );
$viewer = 0; same( 'lavender', request_transport()['body']['appearance']['theme']['id'], 'Logout recomputes global' );
$viewer = 1; save_fixture( 'core', 'original', 1 ); same( 'core', request_transport()['body']['appearance']['theme']['kind'], 'User switch has no stale result' );
$viewer = 2; $role_store->grants = array(); same( 'original', request_transport()['body']['appearance']['density']['selection'], 'Unauthorized Subscriber uses Original after revocation' );
$role_store->grants = array( 'subscriber' => true ); same( 'small', request_transport()['body']['appearance']['density']['selection'], 'Authorized Subscriber restoration uses stored value' );
$old_state = $state; $state['settings']['default_theme'] = builtin( 'mint' ); $viewer = 0;
same( 'mint', request_transport()['body']['appearance']['theme']['id'], 'Global change immediate' ); $state = $old_state;
same( str_repeat( 'b', 32 ), request_transport( array(), array( 'request_id' => str_repeat( 'b', 32 ) ) )['body']['request_id'], 'Fresh caller correlation echoed without identity' );
same( array( 'max_age_seconds' => 30, 'reuse' => false ), request_transport()['body']['refresh'], 'Bounded in-memory life, no reuse' );

$reads = $profile_store->reads;
$policy['blockers'] = array( 'snordian_active' ); $policy['runtime_suspended'] = true; $policy['future_runtime_permitted'] = false;
$r = request_transport(); no_instructions( $r ); same( 'snordian_active', $r['body']['appearance']['admission']['reason'], 'Fresh shared SNORDIAN policy' );
same( $reads, $profile_store->reads, 'No profile reads during suspension' );
$policy['blockers'] = array(); $policy['runtime_suspended'] = false; $policy['future_runtime_permitted'] = true;
same( false, $gate->evaluate( false )['future_runtime_permitted'], 'Context adapter blocks unsupported caller' );
same( false, $gate->evaluate( true )['runtime_enabled'], 'No operational runtime claim' );

$viewer_calls_before = $viewer_calls;
foreach ( array( 'user_id', 'viewer', 'username', 'theme', 'density', 'css', '_wpnonce', 'callback' ) as $forged ) {
	$r = request_transport( array(), array( $forged => '1' ) ); same( 400, $r['status'], 'Unknown/identity field rejected' ); no_instructions( $r );
}
foreach ( array( '', 'ABC', str_repeat( 'a', 33 ), array( 'a' ), 1 ) as $id ) {
	$r = request_transport( array(), array( 'request_id' => $id ) ); same( 400, $r['status'], 'Malformed correlation rejected' );
}
same( 400, request_transport( array(), array(), array( 'user_id' => '1' ) )['status'], 'No query inputs' );
same( 400, request_transport( array(), array(), array( 'action' => Appearance_Endpoint::ACTION ) )['status'], 'Reject duplicate/query action ambiguity' );
foreach ( array( 'GET', 'OPTIONS', 'HEAD', 'PUT', 'DELETE' ) as $method ) {
	$r = request_transport( array( 'REQUEST_METHOD' => $method ) ); same( 405, $r['status'], 'POST-only read, including preflight' ); no_instructions( $r );
}
foreach ( array(
	array( 'HTTP_ORIGIN' => '' ), array( 'HTTP_ORIGIN' => 'null' ), array( 'HTTP_ORIGIN' => 'https://evil.invalid' ),
	array( 'HTTP_ORIGIN' => 'http://wp-test' ), array( 'HTTP_ORIGIN' => 'https://wp-test:444' ),
	array( 'HTTP_ORIGIN' => 'https://wp-test/' ), array( 'HTTP_ORIGIN' => 'https://user@wp-test' ),
	array( 'HTTP_ORIGIN' => 'https://wp-test https://evil.invalid' ), array( 'HTTP_X_PJH5PWP_REQUEST' => '' ),
	array( 'HTTP_SEC_FETCH_SITE' => 'same-site' ), array( 'HTTP_SEC_FETCH_SITE' => 'cross-site' ),
	array( 'HTTP_SEC_FETCH_MODE' => 'navigate' ), array( 'HTTP_SEC_FETCH_DEST' => 'iframe' ),
	array( 'HTTP_REFERER' => '' ), array( 'HTTP_REFERER' => 'https://evil.invalid/lesson/' ),
	array( 'HTTP_REFERER' => 'https://wp-test/wp-admin/admin.php?page=h5p' ),
	array( 'HTTP_REFERER' => 'https://wp-test/wp-admin/admin-ajax.php?action=h5p_embed&id=1' ),
	array( 'HTTP_REFERER' => 'https://wp-test/?preview=true' ), array( 'HTTP_REFERER' => 'https://wp-test/?elementor-preview=2' ),
	array( 'HTTP_REFERER' => 'https://wp-test/wp-json/wp/v2/' ), array( 'HTTP_REFERER' => 'https://wp-test/post/embed/' ),
	array( 'HTTP_REFERER' => 'https://wp-test/wp-login.php' ),
	array( 'HTTP_REFERER' => 'https://wp-test/%77p-admin/' ), array( 'HTTP_REFERER' => 'https://wp-test/../wp-admin/' ),
	array( 'HTTP_REFERER' => 'https://wp-test/%2577p-admin/' ),
) as $bad ) {
	$r = request_transport( $bad ); same( 403, $r['status'], 'Cross-origin/unsupported caller blocked' ); no_instructions( $r );
}
same( $viewer_calls_before, $viewer_calls, 'Invalid requests never resolve identity or preferences' );
$old_server = $server; unset( $server['HTTP_SEC_FETCH_SITE'], $server['HTTP_SEC_FETCH_MODE'], $server['HTTP_SEC_FETCH_DEST'] );
same( 200, request_transport()['status'], 'Older browsers may omit Fetch Metadata; Origin and custom header remain mandatory' ); $server = $old_server;
same( false, WordPress_Context::request_allowed( $server, $home, 'https://admin.invalid/' ), 'Split-origin CMS deployment excluded' );
same( true, WordPress_Context::request_allowed( $server, 'https://wp-test/', $admin ), 'Exact same origin' );
same( false, WordPress_Context::request_allowed( $server, 'https://wp-test/subsite/', $admin ), 'Referer outside home subdirectory excluded' );
$r = request_transport();
foreach ( array( 'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0', 'CDN-Cache-Control' => 'no-store',
	'Surrogate-Control' => 'no-store', 'Pragma' => 'no-cache', 'Vary' => 'Cookie, Origin', 'Cross-Origin-Resource-Policy' => 'same-origin' ) as $key => $value ) {
	same( $value, $r['headers'][ $key ], 'Private/no-store headers' );
}
check( ! isset( $r['headers']['Access-Control-Allow-Origin'] ) && ! isset( $r['headers']['ETag'] ), 'No CORS or conditional reuse' );
$throwing = new Appearance_Endpoint( $service, static function () { throw new RuntimeException( 'PRIVATE_SECRET' ); } );
$r = $throwing->respond( $server, array(), $post, $home, $admin ); no_instructions( $r );
check( false === strpos( json_encode( $r ), 'PRIVATE_SECRET' ), 'No internal exception leakage' );

// Use installed H5P source definitions only, never H5P bootstrap/get_instance/add_assets.
if ( ! isset( $argv[1] ) || ! is_file( $argv[1] . '/autoloader.php' ) ) { throw new RuntimeException( 'Supply installed H5P source root' ); }
require $argv[1] . '/autoloader.php';
$wrapper_source = file_get_contents( $argv[1] . '/public/class-h5p-plugin.php' );
check( false !== strpos( $wrapper_source, "apply_filters('print_h5p_content', \$h5p_content_wrapper, \$content)" ), 'Installed two-argument content filter verified' );
$alter = new ReflectionMethod( 'H5P_Plugin', 'alter_assets' );
same( 3, $alter->getNumberOfParameters(), 'Actual asset hook bridge signature' );
check( $alter->getParameters()[0]->isPassedByReference() && $alter->getParameters()[1]->isPassedByReference(), 'Actual references verified' );
same( 2, ( new ReflectionMethod( 'H5PCore', 'determineEmbedType' ) )->getNumberOfParameters(), 'Actual embed resolver signature' );

$_SERVER = array( 'REQUEST_METHOD' => 'GET', 'HTTP_SEC_FETCH_DEST' => 'document' );
$content = array( 'id' => 10, 'embedType' => 'div', 'library' => array( 'id' => 5, 'name' => 'H5P.Accordion', 'majorVersion' => 1, 'minorVersion' => 0, 'embedTypes' => 'div, iframe' ), 'metadata' => array( 'title' => 'A & B' ) );
function wrapper_for( $content ) {
	$id = $content['id'];
	if ( 'div' === $content['embedType'] ) { return '<div class="h5p-content" data-content-id="' . $id . '"></div>'; }
	return '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $id . '" class="h5p-iframe" data-content-id="' . $id . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no" title="' . esc_attr( $content['metadata']['a11yTitle'] ?? $content['metadata']['title'] ) . '"></iframe></div>';
}
$registry = new Playback_Registry( $gate, array( WordPress_Context::class, 'frontend' ) );
same( wrapper_for( $content ), $registry->observe( wrapper_for( $content ), $content ), 'HTML unchanged byte-for-byte' );
same( array( array( 'content_id' => '10', 'embed' => 'div', 'library' => 'H5P.Accordion 1.0' ) ), $registry->candidates(), 'DIV qualified using main content' );
$second = $content; $second['id'] = 11; $second['embedType'] = 'iframe';
$registry->observe( wrapper_for( $second ), $second );
same( 2, count( $registry->candidates() ), 'Distinct activities supported' ); same( 'iframe', $registry->candidates()[1]['embed'], 'Internal about:blank iframe qualified' );
$registry->observe( wrapper_for( $content ), $content ); same( 1, count( $registry->candidates() ), 'Repeated ID revokes every occurrence of that ID' );
$registry->observe( wrapper_for( $content ), $content ); same( 1, count( $registry->candidates() ), 'Third repetition stays excluded' );
foreach ( array( 'admin_context', 'ajax_context', 'preview_context', 'customize_context', 'embed_context', 'feed_context' ) as $flag ) {
	$$flag = true; same( array(), $registry->candidates(), 'Admin/editor/preview/external/feed excluded' ); $$flag = false;
}
$page_context = false; same( array(), $registry->candidates(), 'Pre-query/editor rendering excluded' ); $page_context = true;
$_SERVER['HTTP_SEC_FETCH_DEST'] = 'iframe'; same( array(), $registry->candidates(), 'Framed/ambiguous parent excluded' ); $_SERVER['HTTP_SEC_FETCH_DEST'] = 'document';
$policy['blockers'] = array( 'snordian_active' ); $policy['future_runtime_permitted'] = false; $policy['runtime_suspended'] = true;
same( array(), $registry->candidates(), 'Late SNORDIAN activation invalidates candidates' );
$policy['blockers'] = array(); $policy['future_runtime_permitted'] = true; $policy['runtime_suspended'] = false;
foreach ( array( 'external', 'editor', '', 'unknown' ) as $embed ) {
	$bad = $content; $bad['embedType'] = $embed;
	$empty = new Playback_Registry( $gate, static function () { return true; } ); $empty->observe( wrapper_for( $content ), $bad ); same( array(), $empty->candidates(), 'Unsupported embed excluded' );
}
foreach ( array( null, array(), array( 'id' => array( 1 ) ), array_replace( $content, array( 'library' => array() ) ),
	array_replace( $content, array( 'id' => '010' ) ), array_replace( $content, array( 'id' => '../10' ) ) ) as $bad ) {
	$empty = new Playback_Registry( $gate, static function () { return true; } ); $empty->observe( wrapper_for( $content ), $bad ); same( array(), $empty->candidates(), 'Ambiguous identity excluded' );
}
foreach ( array( '<div class="h5p-content" data-content-id="999"></div>', wrapper_for( $content ) . wrapper_for( $content ), '<iframe src="https://evil.invalid"></iframe>' ) as $html ) {
	$empty = new Playback_Registry( $gate, static function () { return true; } ); same( $html, $empty->observe( $html, $content ), 'Foreign/ambiguous HTML untouched' ); same( array(), $empty->candidates(), 'Ambiguous ownership excluded' );
}
foreach ( array( null, new stdClass(), 'invalid' ) as $metadata ) {
	$bad = $second; $bad['metadata'] = $metadata;
	$empty = new Playback_Registry( $gate, static function () { return true; } );
	$empty->observe( wrapper_for( $second ), $bad ); same( array(), $empty->candidates(), 'Malformed iframe metadata fails closed' );
}
same( false, $policy['runtime_enabled'], 'No application enabled' );
// Reusing cached wrapper bytes never selects identity or reads personal preferences.
$reads = $profile_store->reads; $public_candidates = null;
foreach ( array( 0, 1, 2, 3 ) as $viewer ) {
	$public = new Playback_Registry( $gate, array( WordPress_Context::class, 'frontend' ) );
	same( wrapper_for( $content ), $public->observe( wrapper_for( $content ), $content ), 'Cached public HTML identical for all viewers' );
	if ( null === $public_candidates ) { $public_candidates = $public->candidates(); }
	same( $public_candidates, $public->candidates(), 'Dormant attachment data is non-personal' );
}
same( $reads, $profile_store->reads, 'Page qualification cannot read personal records' );
if ( '--examples' === ( $argv[2] ?? null ) ) {
	$state = Schema::initial(); $state['settings']['default_theme'] = builtin( 'lavender' ); $viewer = 2;
	$role_store->grants = array( 'subscriber' => true ); $examples = array();
	foreach ( array( 'Core + Original' => array( 'core', 'original' ), 'Lavender + Large' => array( 'builtin:lavender', 'large' ),
		'Mint + Small' => array( 'builtin:mint', 'small' ), 'Site default + Original' => array( 'site_default', 'original' ),
		'Deleted theme fallback' => array( 'custom:deleted', 'large' ) ) as $label => $values ) {
		save_fixture( $values[0], $values[1] ); $examples[ $label ] = request_transport()['body'];
	}
	$policy['blockers'] = array( 'snordian_active' ); $policy['future_runtime_permitted'] = false; $policy['runtime_suspended'] = true;
	$examples['SNORDIAN suspension'] = request_transport()['body'];
	echo json_encode( $examples, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"; exit;
}
echo 'Phase 2B-2B transport/context: ' . $checks . " assertions passed; real domain/profile/role and H5P source; no CMS bootstrap or database.\n";
