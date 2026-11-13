<?php
/** Empty DIV metadata boundary; real installed Core resolver, isolated CMS seams. */
if ( 'cli' !== PHP_SAPI ) { exit; }
ob_start(); require __DIR__ . '/transport.php'; ob_end_clean();
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
require_once dirname( __DIR__, 2 ) . '/includes/Compatibility/class-runtime-policy.php';
function admin_url( $path ) { return 'https://wp-test/wp-admin/' . $path; }
function home_url( $path ) { return 'https://wp-test' . $path; }
$checks = 0;
use Papijo\H5PThemerWordPress\Runtime\Playback_Registry;
use Papijo\H5PThemerWordPress\Runtime\Theme_Attachment;
use Papijo\H5PThemerWordPress\Runtime\WordPress_Context;

$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
$_SERVER = array( 'REQUEST_METHOD' => 'GET', 'HTTP_SEC_FETCH_DEST' => 'document' );
$content = array( 'id' => 3, 'embedType' => 'div', 'library' => array( 'id' => 78, 'name' => 'H5P.AdvancedTextPapiJo', 'majorVersion' => 1, 'minorVersion' => 3, 'embedTypes' => '' ) );
$expected = array( array( 'content_id' => '3', 'embed' => 'div', 'library' => 'H5P.AdvancedTextPapiJo 1.3' ) );
function div_registry() { global $gate; return new Playback_Registry( $gate, array( WordPress_Context::class, 'frontend' ) ); }
same( 'div', H5PCore::determineEmbedType( 'div', '' ), 'Real Core accepts explicit empty metadata as DIV' );
foreach ( array( '', 'div', 'div, iframe', 'iframe, div' ) as $types ) {
 $c = $content; $c['library']['embedTypes'] = $types; $r = div_registry(); $html = wrapper_for( $c );
 same( $html, $r->observe( $html, $c ), 'Qualification preserves exact wrapper bytes' );
 same( $expected, $r->candidates(), 'Empty and nonempty valid metadata admit only the proved DIV' );
}
foreach ( array( null, false, 0, array(), ' ', 'DIV', 'div,iframe', 'external', 'div;iframe', "div\n", 'iframe' ) as $types ) {
 $c = $content; $c['library']['embedTypes'] = $types; $r = div_registry(); $r->observe( wrapper_for( $content ), $c );
 same( array(), $r->candidates(), 'Malformed metadata or Core/wrapper conflict rejected' );
}
$c = $content; unset( $c['library']['embedTypes'] ); $r = div_registry(); $r->observe( wrapper_for( $content ), $c ); same( array(), $r->candidates(), 'Missing is not valid empty metadata' );
foreach ( array( 'external', 'editor', 'unknown', '', null, array() ) as $embed ) {
 $c = $content; $c['embedType'] = $embed; $r = div_registry(); $r->observe( wrapper_for( $content ), $c ); same( array(), $r->candidates(), 'Unsupported content context excluded' );
}
foreach ( array( '03', '0', '../3', array(), null ) as $id ) {
 $c = $content; $c['id'] = $id; $r = div_registry(); $r->observe( wrapper_for( $content ), $c ); same( array(), $r->candidates(), 'Invalid identity excluded with empty metadata' );
}
$r = div_registry(); $r->observe( wrapper_for( $content ), $content ); $r->observe( wrapper_for( $content ), $content ); same( array(), $r->candidates(), 'Repeated ID remains ambiguous' );
foreach ( array( 'admin_context', 'ajax_context', 'preview_context', 'customize_context', 'embed_context', 'feed_context' ) as $flag ) {
 $$flag = true; $r = div_registry(); $r->observe( wrapper_for( $content ), $content ); same( array(), $r->candidates(), 'Frontend exclusions hold for empty metadata' ); $$flag = false;
}
$r = div_registry(); $nested = '<div class="h5p-content" data-content-id="99">' . wrapper_for( $content ) . '</div>';
same( $nested, $r->observe( $nested, $content ), 'Nested/combined wrapper unchanged' ); same( array(), $r->candidates(), 'Nested wrapper is not a canonical main wrapper' );
foreach ( array( 'iframe', '' ) as $types ) {
 $c = $content; $c['embedType'] = 'iframe'; $c['library']['embedTypes'] = $types; $c['metadata'] = array( 'title' => 'Isolated iframe' );
 $r = div_registry(); $r->observe( wrapper_for( $c ), $c ); same( $types === 'iframe' ? 1 : 0, count( $r->candidates() ), 'Explicit internal iframe preserved; empty iframe is outside this narrow correction' );
}
$r = div_registry(); $r->observe( '<iframe src="https://foreign.invalid/"></iframe>', $content ); same( array(), $r->candidates(), 'External wrapper never inferred as DIV' );
$r = div_registry(); $r->observe( wrapper_for( $content ), $content );
same( $expected, ( new Theme_Attachment( $r ) )->manifest()['players'], 'Default-on attachment reuses admitted DIV metadata' );
$policy = array( 'future_runtime_permitted' => false, 'runtime_suspended' => true, 'blockers' => array( 'snordian_active' ) );
same( array(), $r->candidates(), 'Late SNORDIAN suspension revokes already admitted DIV' );
$r = div_registry(); $r->observe( wrapper_for( $content ), $content ); same( array(), $r->candidates(), 'Active SNORDIAN prevents initial DIV admission' );
no_instructions( request_transport() );
echo 'Phase 2B-3B-1C DIV admission: ' . $checks . " assertions passed; real Core resolver, isolated fixtures, no live activation or database.\n";
