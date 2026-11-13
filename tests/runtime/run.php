<?php
/** Isolated runtime contracts; real domain/profile/role services, disposable doubles only. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $n, $m, $f, $l ) { throw new ErrorException( $m, 0, $n, $f, $l ); } );
require dirname( __DIR__, 2 ) . '/includes/runtime.php';
require dirname( __DIR__, 2 ) . '/includes/profiles.php';

use Papijo\H5PThemerWordPress\Runtime\Appearance_Service;
use Papijo\H5PThemerWordPress\Runtime\CSS_Serializer;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Profile\Profile_Service;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
use Papijo\H5PThemerWordPress\Role\Role_Service;
use Papijo\H5PThemerWordPress\Storage\Schema;

$checks = 0;
function check( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) { throw new RuntimeException( $message ); } }
function same( $expected, $actual, $message ) { check( $expected === $actual, $message ); }
function invalid( $result ) { same( null, $result['data'], 'No partial CSS on rejection' ); same( array( 'invalid_runtime_colors' ), $result['errors'], 'Constant validation diagnostic' ); }
function builtin( $id ) { return array( 'kind' => 'builtin', 'id' => $id, 'version' => Preset_Catalog::VERSION ); }
function definition( $schema = 2 ) {
	$d = array( 'schema_version' => $schema, 'id' => 'example', 'label' => 'Private label', 'density' => 'large', 'colors' => array( '--h5p-theme-background' => '#AbC' ) );
	if ( 2 === $schema ) { $d['base_preset'] = array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ); }
	return $d;
}
// These are read-only CMS seams. No actor/current-user permission is substituted.
$multisite = false;
$registered = array( 'administrator' => array( 'name' => 'Administrator', 'capabilities' => array( 'manage_options' => true ) ),
	'learner' => array( 'name' => 'Learner', 'capabilities' => array() ), 'blocked' => array( 'name' => 'Blocked', 'capabilities' => array() ) );
$users = array( 1 => (object) array( 'ID' => 1, 'roles' => array( 'administrator' ), 'admin' => true ),
	2 => (object) array( 'ID' => 2, 'roles' => array( 'learner' ), 'admin' => false ),
	3 => (object) array( 'ID' => 3, 'roles' => array( 'blocked' ), 'admin' => false ),
	4 => (object) array( 'ID' => 4, 'roles' => array(), 'admin' => false ) );
function is_multisite() { global $multisite; return $multisite; }
function get_userdata( $id ) { global $users; return $users[ $id ] ?? false; }
function user_can( $user, $cap ) { return 'manage_options' === $cap && $user->admin; }
function wp_roles() { global $registered; return (object) array( 'roles' => $registered ); }
function translate_user_role( $label ) { return $label; }

$role_store = new class {
	public $grants = array( 'learner' => true );
	public function read() { return array( 'schema_version' => 1, 'revision' => 0, 'roles' => $this->grants ); }
};
$profile_store = new class {
	public $values = array(); public $fail = false; public $reads = 0;
	public function read( $id ) {
		++$this->reads;
		if ( $this->fail ) { throw new RuntimeException( 'Storage unavailable' ); }
		return isset( $this->values[ $id ] ) ? array( 'values' => $this->values[ $id ] ) : null;
	}
};
$state = Schema::initial();
$state['settings']['default_theme'] = builtin( 'lavender' );
$profiles = new Profile_Service( static function () use ( &$state ) { return $state; }, $profile_store, new Role_Service( $role_store ) );
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
$admissions = 0;
$service = new Appearance_Service( static function () use ( &$policy, &$admissions ) { ++$admissions; return $policy; }, array( $profiles, 'effective' ) );
function save_fixture( $theme, $density, $id = 2 ) {
	global $profile_store; $profile_store->values[ $id ] = array_replace( Profile_State::defaults(), array( 'theme' => $theme, 'density' => $density ) );
}
foreach ( array( 0, 3, 4, 999 ) as $id ) {
	save_fixture( 'builtin:mint', 'small', $id );
	$d = $service->describe( $id );
	same( 'global', $d['theme']['source'], 'Anonymous/unauthorized use global' );
	same( 'lavender', $d['theme']['id'], 'Current global palette' );
	same( array( 'selection' => 'original', 'preserve_native' => true ), $d['density'], 'Anonymous/unauthorized preserve native' );
}
foreach ( array( 1, 2 ) as $id ) {
	foreach ( array( 'core', 'builtin:daylight', 'builtin:lavender', 'builtin:mint', 'builtin:sunset', 'builtin:dark', 'site_default' ) as $token ) {
		foreach ( array( 'original', 'small', 'medium', 'large' ) as $density ) {
			save_fixture( $token, $density, $id );
			$d = $service->describe( $id );
			same( array( 'permitted' => true, 'reason' => null ), $d['admission'], 'Administrator and granted role admitted' );
			same( 'site_default' === $token ? 'global' : 'personal', $d['theme']['source'], 'Selection source' );
			same( array( 'selection' => $density, 'preserve_native' => 'original' === $density ), $d['density'], 'Independent native Density intent' );
			same( 'core' === $token ? 'core' : 'builtin', $d['theme']['kind'], 'Effective kind' );
			if ( 'core' === $token ) { same( '', $d['color_declarations'], 'Explicit Core never inherits global CSS' ); }
			else { check( strlen( $d['color_declarations'] ) > 0, 'Every preset has declarations' ); }
			same( $d, $service->describe( $id ), 'Deterministic complete descriptor' );
		}
	}
}
// Publication flags cannot remove any of the five personal built-ins.
foreach ( array( 'lavender', 'mint', 'sunset', 'dark' ) as $id ) { $state['publication']['themes'][ 'builtin:' . $id ] = false; }
foreach ( array( 'daylight', 'lavender', 'mint', 'sunset', 'dark' ) as $id ) {
	save_fixture( 'builtin:' . $id, 'large' ); same( $id, $service->describe( 2 )['theme']['id'], 'All built-ins remain selectable' );
}
$state['definitions']['example'] = definition();
$state['publication']['themes']['custom:example'] = true;
save_fixture( 'custom:example', 'small' );
$d = $service->describe( 2 ); same( 'custom', $d['theme']['kind'], 'Published custom selectable' );
check( false !== strpos( $d['color_declarations'], '--h5p-theme-background:#AbC;' ), 'Custom hex override serialized' );
same( 'small', $d['density']['selection'], 'Legacy large metadata does not replace Small' );
$state['definitions']['example']['density'] = 'medium'; same( $d, $service->describe( 2 ), 'Legacy metadata cannot change descriptor' );
$state['publication']['themes']['custom:example'] = false;
same( 'lavender', $service->describe( 2 )['theme']['id'], 'Unpublished personal falls back to global' );
same( 'small', $service->describe( 2 )['density']['selection'], 'Theme fallback preserves Density' );
$state['settings']['default_theme'] = array( 'kind' => 'custom', 'id' => 'example' );
save_fixture( 'site_default', 'original' );
same( 'custom', $service->describe( 2 )['theme']['kind'], 'Unpublished custom is globally eligible' );
same( 'example', $service->describe( 0 )['theme']['id'], 'Anonymous gets unpublished global theme' );
$state['settings']['default_theme'] = builtin( 'mint' );
same( 'mint', $service->describe( 2 )['theme']['id'], 'Site default resolved afresh' );
foreach ( array( 'custom:missing', 'custom:example', '../bad', null, array( 'core' ) ) as $token ) {
	save_fixture( $token, 'large' ); same( 'mint', $service->describe( 2 )['theme']['id'], 'Invalid/unavailable personal reference falls back' );
}
unset( $state['definitions']['example'] ); save_fixture( 'custom:example', 'small' );
same( 'mint', $service->describe( 2 )['theme']['id'], 'Deleted reference falls back' );
foreach ( array( 'huge', '', null, 1, array( 'small' ) ) as $density ) {
	save_fixture( 'builtin:lavender', $density );
	same( array( 'selection' => 'original', 'preserve_native' => true ), $service->describe( 2 )['density'], 'Invalid stored Density preserves native' );
}
$profile_store->fail = true;
same( 'global', $service->describe( 2 )['theme']['source'], 'Profile failure reuses existing safe defaults' );
same( 'original', $service->describe( 2 )['density']['selection'], 'Unreadable profile preserves native' ); $profile_store->fail = false;
foreach ( array( array( 'kind' => 'custom', 'id' => 'missing' ), array( 'kind' => 'builtin', 'id' => 'mint', 'version' => 'unavailable' ), 'bad' ) as $global ) {
	$state['settings']['default_theme'] = $global; save_fixture( 'site_default', 'large' );
	$d = $service->describe( 2 ); same( 'core', $d['theme']['kind'], 'Invalid global fails to Core' ); same( '', $d['color_declarations'], 'Broken global has no colors' ); same( 'large', $d['density']['selection'], 'Global failure independent of Density' );
}
$state = Schema::initial(); save_fixture( 'builtin:mint', 'small' );
$role_store->grants = array(); same( 'core', $service->describe( 2 )['theme']['kind'], 'Revoked role uses global Core' ); same( 'original', $service->describe( 2 )['density']['selection'], 'Revoked role preserves native' );
save_fixture( 'builtin:lavender', 'large', 1 ); same( 'lavender', $service->describe( 1 )['theme']['id'], 'Administrator automatically authorized without role grant' );
$role_store->grants = array( 'learner' => true ); same( 'mint', $service->describe( 2 )['theme']['id'], 'Restored permission reuses saved theme' );
unset( $registered['learner'] ); same( 'core', $service->describe( 2 )['theme']['kind'], 'Deleted role cannot authorize stale assignment' );
$registered['learner'] = array( 'name' => 'Renamed', 'capabilities' => array() );
$before = serialize( array( $state, $profile_store->values, $role_store->grants ) );
$d = $service->describe( 2 );
same( array( 'admission', 'theme', 'color_declarations', 'density' ), array_keys( $d ), 'Minimal descriptor projection' );
same( array( 'source', 'kind', 'id', 'version' ), array_keys( $d['theme'] ), 'No label/profile/catalog/revision/provenance leakage' );
same( $before, serialize( array( $state, $profile_store->values, $role_store->grants ) ), 'Read resolution preserves all fixtures' );

$serializer = new CSS_Serializer(); $resolver = new Palette_Resolver();
same( '', $serializer->serialize( $resolver->resolve( null )['data'] )['data'], 'Core serializer empty' );
foreach ( ( new Preset_Catalog() )->all()['data'] as $id => $preset ) {
	$theme = $resolver->resolve( builtin( $id ) )['data']; $css = $serializer->serialize( $theme );
	same( array(), $css['errors'], 'Pinned built-in accepted' );
	$expected = ''; foreach ( Color_Properties::supported() as $property ) { $expected .= $property . ':' . $preset['colors'][ $property ] . ';'; }
	same( $expected, $css['data'], 'Canonical supported-property ordering' );
	check( false === strpos( $css['data'], '--h5p-theme-contrast-cta-dark:' ), 'Catalog-only property never emitted' );
	$theme['colors'] = array_reverse( $theme['colors'], true ); same( $css, $serializer->serialize( $theme ), 'Input ordering irrelevant' );
	$theme['label'] = '</style><script>bad</script>'; $theme['density'] = 'small';
	same( $css, $serializer->serialize( $theme ), 'Labels and legacy Density never enter CSS' );
	$theme['colors']['--h5p-theme-background'] = '#123'; invalid( $serializer->serialize( $theme ) );
}
$theme = $resolver->resolve( array( 'kind' => 'custom', 'id' => 'example' ), array( 'example' => definition() ) )['data'];
$css = $serializer->serialize( $theme ); same( array(), $css['errors'], 'Pinned inherited expression accepted' );
check( false !== strpos( $css['data'], 'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)' ), 'Exact pinned expression retained' );
$legacy = $resolver->resolve( array( 'kind' => 'custom', 'id' => 'example' ), array( 'example' => definition( 1 ) ) )['data'];
same( '--h5p-theme-background:#AbC;', $serializer->serialize( $legacy )['data'], 'Sparse legacy palette hex-only' );
foreach ( array( '#abc', '#ABCDEF', '#012345', '#aBcDeF' ) as $hex ) { $t = $legacy; $t['colors']['--h5p-theme-background'] = $hex; same( '--h5p-theme-background:' . $hex . ';', $serializer->serialize( $t )['data'], 'Exact validated hex retained' ); }
foreach ( array( '#12', '#abcd', '#12345678', 'red', 'rgb(0,0,0)', 'url(https://example.invalid)', '#abc;background:red', '#abc!important', "#abc\n", '#abc/*x*/', 'var(--evil)', 'expression(alert(1))', '</style>', array(), null, false, 42 ) as $value ) {
	$t = $legacy; $t['colors']['--h5p-theme-background'] = $value; invalid( $serializer->serialize( $t ) );
}
foreach ( array( '--evil', 'color', '--h5p-theme-density', '.h5p-content', '--h5p-theme-background;}', '--h5p-theme-contrast-cta-dark', 0 ) as $property ) {
	$t = $legacy; $t['colors'][ $property ] = '#abc'; invalid( $serializer->serialize( $t ) );
}
$expression = $theme['colors']['--h5p-theme-contrast-cta-light'];
$t = $legacy; $t['colors']['--h5p-theme-contrast-cta-light'] = $expression; invalid( $serializer->serialize( $t ) );
$t = $theme; $t['colors']['--h5p-theme-background'] = $expression; invalid( $serializer->serialize( $t ) );
$t = $theme; $t['colors']['--h5p-theme-contrast-cta-light'] = str_replace( '90%', '89%', $expression ); invalid( $serializer->serialize( $t ) );
$t = $theme; $t['colors']['--h5p-theme-contrast-cta-dark'] = '#123'; invalid( $serializer->serialize( $t ) );
$t = $theme; $t['colors'] = array_reverse( $t['colors'], true ); same( $css, $serializer->serialize( $t ), 'Inherited custom ordering canonical' );
$t = $theme; $t['colors']['--evil'] = '#abc'; $t['unsupported_properties'][] = '--evil'; invalid( $serializer->serialize( $t ) );
$t = $theme; $t['base_preset'] = null; $t['provenance'] = array( 'trusted' => true ); invalid( $serializer->serialize( $t ) );
$t = $resolver->resolve( builtin( 'mint' ) )['data']; $t['version'] = 'unavailable'; invalid( $serializer->serialize( $t ) );
$t = $resolver->resolve( builtin( 'mint' ) )['data']; unset( $t['colors']['--h5p-theme-background'] ); invalid( $serializer->serialize( $t ) );
foreach ( array( array( 'id' => 'mint', 'version' => 'unknown' ), array( 'id' => 'unknown', 'version' => Preset_Catalog::VERSION ), array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION, 'evil' => true ), 'mint' ) as $base ) {
	$t = $theme; $t['base_preset'] = $base; invalid( $serializer->serialize( $t ) );
}
foreach ( array( null, '', array(), array( 'kind' => 'evil', 'colors' => array() ), array( 'kind' => 'core', 'colors' => array( '--h5p-theme-background' => '#abc' ) ), array( 'kind' => 'builtin', 'colors' => array() ) ) as $bad ) { invalid( $serializer->serialize( $bad ) ); }

$reads = $profile_store->reads;
foreach ( array( null, array(), array( 'future_runtime_permitted' => true ), array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array( 'snordian_active' ) ), array( 'future_runtime_permitted' => 1, 'runtime_suspended' => false, 'blockers' => array() ) ) as $bad_policy ) {
	$policy = $bad_policy; $d = $service->describe( 2 ); same( false, $d['admission']['permitted'], 'Malformed/inconsistent admission fails closed' ); same( null, $d['theme'], 'Suspended has no theme' ); same( '', $d['color_declarations'], 'Suspended has no color instruction' ); same( null, $d['density'], 'Suspended has no Density instruction' );
}
same( $reads, $profile_store->reads, 'Suspension skips profile reads' );
$failure = new Appearance_Service( static function () { throw new RuntimeException( 'secret' ); }, array( $profiles, 'effective' ) );
same( 'admission_unavailable', $failure->describe( 2 )['admission']['reason'], 'Admission exception is constant and inert' );
$policy = array( 'future_runtime_permitted' => true, 'runtime_suspended' => false, 'blockers' => array() );
$failure = new Appearance_Service( static function () use ( &$policy ) { return $policy; }, static function () { throw new RuntimeException( 'secret' ); } );
same( 'appearance_unavailable', $failure->describe( 2 )['admission']['reason'], 'Effective exception is constant and inert' );
foreach ( array( null, array(), array( 'theme' => array( 'data' => $theme, 'errors' => array( 'bad' ), 'source' => 'global' ) ), array( 'theme' => array( 'data' => array( 'kind' => 'custom', 'colors' => array() ), 'errors' => array(), 'source' => 'personal' ) ) ) as $bad_effective ) {
	$failure = new Appearance_Service( static function () use ( $policy ) { return $policy; }, static function () use ( $bad_effective ) { return $bad_effective; } );
	same( null, $failure->describe( 2 )['density'], 'Broken effective result gives no actionable instructions' );
}
$count_before = $admissions; $service->describe( 2 ); $service->describe( 2 ); same( $count_before + 2, $admissions, 'Admission is fresh on every call' );
// Reproducible full descriptor examples; stdout only, no artifact/storage writes.
if ( '--examples' === ( $argv[1] ?? null ) ) {
	$state = Schema::initial(); $state['settings']['default_theme'] = builtin( 'lavender' );
	$examples = array();
	foreach ( array( 'Core + Original' => array( 'core', 'original' ), 'Lavender + Large' => array( 'builtin:lavender', 'large' ),
		'Mint + Small' => array( 'builtin:mint', 'small' ), 'Site default + Original' => array( 'site_default', 'original' ) ) as $label => $values ) {
		save_fixture( $values[0], $values[1] ); $examples[ $label ] = $service->describe( 2 );
	}
	$policy = array( 'future_runtime_permitted' => false, 'runtime_suspended' => true, 'blockers' => array( 'snordian_active' ) );
	$examples['Suspended due to SNORDIAN'] = $service->describe( 2 );
	echo json_encode( $examples, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"; exit;
}
echo 'Phase 2B-2A runtime: ' . $checks . " assertions passed; no CMS bootstrap or database.\n";
