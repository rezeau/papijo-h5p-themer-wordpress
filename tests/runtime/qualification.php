<?php
/** Sequential cache/identity/policy qualification with real services and memory fixtures. */
if ( 'cli' !== PHP_SAPI ) { exit; }
ob_start(); require __DIR__ . '/run.php'; ob_end_clean();
require dirname( __DIR__, 2 ) . '/includes/runtime-wordpress.php';
if ( ! isset( $argv[1] ) || ! is_file( $argv[1] . '/autoloader.php' ) ) { throw new RuntimeException( 'Supply installed H5P source root' ); }
require $argv[1] . '/autoloader.php'; // Definitions only, no WordPress/H5P bootstrap.

use Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint;
use Papijo\H5PThemerWordPress\Runtime\Appearance_Service;
use Papijo\H5PThemerWordPress\Runtime\Playback_Registry;
use Papijo\H5PThemerWordPress\Runtime\Runtime_Admission;
use Papijo\H5PThemerWordPress\Runtime\Theme_Attachment;
use Papijo\H5PThemerWordPress\Storage\Schema;

function admin_url( $path ) { return 'https://fixture.test/wp-admin/' . $path; }
function home_url( $path ) { return 'https://fixture.test' . $path; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function update_option() { throw new RuntimeException( 'Qualification must never write' ); }
function update_user_meta() { throw new RuntimeException( 'Qualification must never write' ); }

$checks = 0; $state = Schema::initial(); $state['settings']['default_theme'] = builtin( 'lavender' );
$registered['learner'] = array( 'name' => 'Learner', 'capabilities' => array() );
$users[2]->roles = $users[3]->roles = array( 'learner' );
$role_store->grants = array( 'learner' => true ); $profile_store->fail = false;
save_fixture( 'builtin:mint', 'small', 2 ); save_fixture( 'builtin:sunset', 'medium', 3 );
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
$gate = new Runtime_Admission( static function () use ( &$policy ) { return $policy; } );
$appearance = new Appearance_Service( static function () use ( $gate ) { return $gate->evaluate( true ); }, array( $profiles, 'effective' ) );
$viewer = 0; $identity_reads = 0;
$endpoint = new Appearance_Endpoint( $appearance, static function () use ( &$viewer, &$identity_reads ) { ++$identity_reads; return $viewer; } );
$server = array( 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'https://fixture.test', 'HTTP_REFERER' => 'https://fixture.test/lesson/',
	'HTTP_X_PJH5PWP_REQUEST' => 'appearance-v1' );
$post = array( 'action' => Appearance_Endpoint::ACTION, 'request_id' => str_repeat( 'a', 32 ) );
$registry = new Playback_Registry( $gate, static function () { return true; } );
$content = array( 'id' => 10, 'embedType' => 'div', 'library' => array( 'id' => 1, 'name' => 'H5P.Accordion', 'majorVersion' => 1, 'minorVersion' => 0, 'embedTypes' => 'div' ) );
$html = '<div class="h5p-content" data-content-id="10"></div>';
$registry->observe( $html, $content );
$attachment = new Theme_Attachment( $registry, static function () { return true; } );
$tag = '<script src="https://fixture.test/controller.js"></script>';
$public = $attachment->tag( $tag, Theme_Attachment::HANDLE );

function current_appearance( $theme, $density, $kind = 'builtin' ) {
	global $endpoint, $server, $post, $identity_reads, $state, $profile_store, $role_store, $attachment, $tag, $public;
	$before = serialize( array( $state, $profile_store->values, $role_store->grants ) ); $reads = $identity_reads;
	$r = $endpoint->respond( $server, array(), $post, home_url( '/' ), admin_url( '' ) );
	same( $reads + 1, $identity_reads, 'Every valid request resolves current server viewer' );
	same( 200, $r['status'], 'Fresh appearance request succeeds' );
	same( $theme, $r['body']['appearance']['theme']['id'], 'Current Theme with no viewer/policy memoization' );
	same( $kind, $r['body']['appearance']['theme']['kind'], 'Current Theme kind' );
	same( $density, $r['body']['appearance']['density']['selection'], 'Current independent Density' );
	same( Appearance_Endpoint::headers(), $r['headers'], 'Every identity receives private/no-store headers' );
	same( $before, serialize( array( $state, $profile_store->values, $role_store->grants ) ), 'Resolution never rewrites fixtures' );
	$profile_reads = $profile_store->reads;
	same( $public, $attachment->tag( $tag, Theme_Attachment::HANDLE ), 'Identical public tag/manifest across account and policy changes' );
	same( $profile_reads, $profile_store->reads, 'Public attachment never reads preferences' );
	return $r['body']['appearance'];
}
// One endpoint, one public attachment and identical request correlation across
// anonymous -> A -> B -> logged out -> A: no response/profile authority is reused.
foreach ( array( array( 0, 'lavender', 'original' ), array( 2, 'mint', 'small' ), array( 3, 'sunset', 'medium' ),
	array( 0, 'lavender', 'original' ), array( 2, 'mint', 'small' ) ) as $case ) {
	$viewer = $case[0]; current_appearance( $case[1], $case[2] );
}
$role_store->grants = array(); current_appearance( 'lavender', 'original' );
$role_store->grants = array( 'learner' => true ); current_appearance( 'mint', 'small' );
$users[2]->roles = array( 'blocked' ); current_appearance( 'lavender', 'original' );
$users[2]->roles = array( 'learner' ); current_appearance( 'mint', 'small' );
save_fixture( 'builtin:dark', 'small' ); current_appearance( 'dark', 'small' );
save_fixture( 'builtin:dark', 'large' ); current_appearance( 'dark', 'large' );
save_fixture( 'site_default', 'medium' ); $state['settings']['default_theme'] = builtin( 'mint' ); current_appearance( 'mint', 'medium' );
$viewer = 0; current_appearance( 'mint', 'original' ); $viewer = 2;
$state['definitions']['example'] = definition(); $state['publication']['themes']['custom:example'] = true;
save_fixture( 'custom:example', 'small' ); current_appearance( 'example', 'small', 'custom' );
$state['publication']['themes']['custom:example'] = false; current_appearance( 'mint', 'small' );
$state['publication']['themes']['custom:example'] = true; current_appearance( 'example', 'small', 'custom' );
unset( $state['definitions']['example'] ); current_appearance( 'mint', 'small' );
$state['definitions']['example'] = definition(); $state['definitions']['example']['colors']['--h5p-theme-background'] = 'url(evil)';
current_appearance( null, 'small', 'core' ); // Invalid aggregate uses the existing whole-library fallback.
unset( $state['definitions']['example'] ); current_appearance( 'mint', 'small' );
$reads = $identity_reads;
$forged = $post; $forged['user_id'] = 3;
same( 400, $endpoint->respond( $server, array(), $forged, home_url( '/' ), admin_url( '' ) )['status'], 'Forged user identity is rejected' );
same( $reads, $identity_reads, 'Rejected identity never reaches server resolver' );

// Admission changes are simulated; no foreign plugin is activated/deactivated.
$profile_reads = $profile_store->reads;
$policy = array( 'future_runtime_permitted' => false, 'runtime_suspended' => true, 'blockers' => array( 'snordian_active' ) );
$r = $endpoint->respond( $server, array(), $post, home_url( '/' ), admin_url( '' ) );
same( array( 'admission' => array( 'permitted' => false, 'reason' => 'snordian_active' ), 'theme' => null, 'color_declarations' => '', 'density' => null ), $r['body']['appearance'], 'Late SNORDIAN suspension has no actionable styling' );
same( $profile_reads, $profile_store->reads, 'Suspension never resolves private preferences' );
same( '', $attachment->tag( $tag, Theme_Attachment::HANDLE ), 'Late suspension suppresses even an already queued tag' );
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
current_appearance( 'mint', 'small' );
echo 'Phase 2B-2E cache/identity qualification: ' . $checks . " assertions passed; sequential disposable identities/policy changes, real domain/profile/role services.\n";
