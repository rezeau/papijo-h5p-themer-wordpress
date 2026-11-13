<?php
/** Pure-domain tests and live-source parity oracle. CLI only, no WordPress. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-domain.php';
require __DIR__ . '/in-memory-store.php';

use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Validator;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Publication\Publication_Policy;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Tests\In_Memory_Store;

$count = 0; $parity = 0;
function check( $condition, $message ) {
	global $count;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$count;
}
function same( $expected, $actual, $message ) { check( $expected === $actual, $message ); }
function ok( $result, $message ) { check( empty( $result['errors'] ), $message ); return $result['data']; }
function bad( $result, $message ) { check( null === $result['data'] && ! empty( $result['errors'] ), $message ); }
function builtin( $id ) { return array( 'kind' => 'builtin', 'id' => $id, 'version' => Preset_Catalog::VERSION ); }
function custom( $id ) { return array( 'kind' => 'custom', 'id' => $id ); }
function definition( $id = 'example', $schema = 2 ) {
	$data = array( 'schema_version' => $schema, 'id' => $id, 'label' => 'Example', 'description' => 'Original description', 'density' => 'large', 'colors' => array() );
	if ( 2 === $schema ) { $data['base_preset'] = array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ); }
	return $data;
}
$catalog = new Preset_Catalog(); $validator = new Theme_Validator(); $resolver = new Palette_Resolver();
$operations = new Theme_Operations(); $publication = new Publication_Policy(); $changes = new Library_Changes();
$palettes = ok( $catalog->all(), 'Catalog loads' );
same( array( 'daylight', 'lavender', 'mint', 'sunset', 'dark' ), array_keys( $palettes ), 'Stable builtin identities' );
same( 33, count( Color_Properties::supported() ), 'Supported property inventory' );
$group_properties = array();
foreach ( Color_Properties::groups() as $group ) { foreach ( $group['fields'] as $suffix => $label ) { $group_properties[] = '--h5p-theme-' . $suffix; } }
$sorted = Color_Properties::supported(); sort( $sorted ); sort( $group_properties ); same( $sorted, $group_properties, 'Groups cover each supported property exactly once' );
foreach ( $palettes as $id => $preset ) {
	same( 34, count( $preset['colors'] ), '34 catalog values: ' . $id );
	foreach ( Color_Properties::supported() as $property ) { check( array_key_exists( $property, $preset['colors'] ), 'Preset supports ' . $property ); }
	same( array( '--h5p-theme-contrast-cta-dark' ), $preset['unsupported_properties'], 'Catalog-only property tracked' );
	$copy = $catalog->get( $id, Preset_Catalog::VERSION ); $copy['data']['colors']['--h5p-theme-background'] = '#123';
	same( $preset, $catalog->get( $id, Preset_Catalog::VERSION )['data'], 'No reference mutation of builtins' );
}
foreach ( array( '', 'unknown', null, 1, array() ) as $version ) { bad( $catalog->all( $version ), 'Unknown catalog version' ); }
foreach ( array( '', 'Mint', null, array(), 'missing' ) as $id ) { bad( $catalog->get( $id, Preset_Catalog::VERSION ), 'Unknown builtin ID' ); }
foreach ( array( 'a', 'a_1', str_repeat( 'a', 64 ), 'mint' ) as $id ) { check( Theme_Validator::valid_id( $id ), 'Valid ID' ); }
foreach ( array( '', 'A', '_id', '1id', 'a-b', 'a b', 'é', 'a.b', "a\n", str_repeat( 'a', 65 ), null, true, array() ) as $id ) { check( ! Theme_Validator::valid_id( $id ), 'Invalid ID' ); }
$colors = array( '#abc', '#ABC', '#aBc123', '#000000', '#fff' );
$invalid_colors = array( '', '#12', '#1234', '#12345678', '#ggg', 'red', 'var(--color)', 'url(x)', ' #fff', '#fff ', "#fff\n", null, 123, false, array() );
foreach ( Color_Properties::supported() as $property ) {
	foreach ( $colors as $color ) { $d = definition(); $d['colors'] = array( $property => $color ); same( $color, ok( $validator->definition( 'example', $d ), 'Hex accepted verbatim' )['colors'][ $property ], 'No case/shorthand normalization' ); }
	foreach ( $invalid_colors as $color ) { $d = definition(); $d['colors'] = array( $property => $color ); bad( $validator->definition( 'example', $d ), 'Invalid hex rejected on every property' ); }
	$d = definition(); $d['colors'][ $property ] = '#ABC';
	$resolved = ok( $resolver->resolve( custom( 'example' ), array( 'example' => $d ) ), 'Sparse inheritance' );
	$expected = array_replace( $palettes['mint']['colors'], $d['colors'] ); same( $expected, $resolved['colors'], 'Only explicit override changes' );
	$cleared = ok( $operations->with_overrides( $d, array( $property => '' ) ), 'Clear override' );
	same( $palettes['mint']['colors'], ok( $resolver->resolve( custom( 'example' ), array( 'example' => $cleared ) ), 'Re-resolve cleared' )['colors'], 'Clear restores pinned value' );
}
foreach ( array( '--h5p-theme-contrast-cta-dark', '--h5p-theme-font-size-m', '--evil', 0 ) as $property ) {
	$d = definition(); $d['colors'] = array( $property => '#fff' ); bad( $validator->definition( 'example', $d ), 'Unknown/catalog-only property rejected as override' );
	bad( $operations->with_overrides( definition(), array( $property => '' ) ), 'Unknown blank key not silently discarded' );
}
$definitions = array( 'example' => definition(), 'legacy' => definition( 'legacy', 1 ) );
$definitions['example']['colors'] = array( '--h5p-theme-background' => '#AbC' );
$definitions['legacy']['colors'] = array( '--h5p-theme-focus' => '#123456' );
same( $definitions['legacy']['colors'], ok( $resolver->resolve( custom( 'legacy' ), $definitions ), 'Schema 1 sparse Core cascade' )['colors'], 'Schema 1 not flattened' );
foreach ( array( null, false, 1, 'x', new stdClass(), array() ) as $malformed ) { bad( $validator->definition( 'example', $malformed ), 'Malformed definition safe' ); }
foreach ( array( 'schema_version' => '2', 'id' => 'other', 'label' => '', 'density' => 'compact', 'colors' => null, 'base_preset' => custom( 'legacy' ) ) as $field => $value ) {
	$d = definition(); $d[ $field ] = $value; bad( $validator->definition( 'example', $d ), 'Invalid field: ' . $field );
}
foreach ( array( 'schema_version', 'id', 'label', 'density', 'colors', 'base_preset' ) as $field ) { $d = definition(); unset( $d[ $field ] ); bad( $validator->definition( 'example', $d ), 'Missing required field' ); }
foreach ( array( 'parent', 'kind', 'published', '_config_name' ) as $extra ) { $d = definition(); $d[ $extra ] = 'x'; bad( $validator->definition( 'example', $d ), 'Strict definition fields' ); }
foreach ( array( str_repeat( 'x', 256 ), "bad\0label", "bad\nlabel", "\xFF", array() ) as $label ) {
	$d = definition(); $d['label'] = $label; bad( $validator->definition( 'example', $d ), 'Invalid label bytes/type' );
}
$d = definition(); $d['label'] = str_repeat( 'x', 255 ); ok( $validator->definition( 'example', $d ), '255-byte label boundary' );
foreach ( array( str_repeat( 'x', 4097 ), "bad\0description", "\xFF", null, array() ) as $description ) {
	$d = definition(); $d['description'] = $description; bad( $validator->definition( 'example', $d ), 'Invalid description bytes/type' );
}
$d = definition(); $d['description'] = str_repeat( 'x', 4096 ); ok( $validator->definition( 'example', $d ), '4096-byte description boundary' );
$d['description'] = "Multiline\ntext\tallowed"; ok( $validator->definition( 'example', $d ), 'Description line breaks/tabs retained' );
foreach ( array( null, array(), array( 'id' => 'mint' ), array( 'id' => 'mint', 'version' => 'latest' ), array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION, 'parent' => 'x' ) ) as $base ) { bad( $validator->base( $base ), 'Exact pin required' ); }
$references = array( null, builtin( 'mint' ), custom( 'example' ), custom( 'missing' ) );
foreach ( $references as $reference ) { ok( $validator->reference( $reference ), 'Reference shape independent of existence' ); }
foreach ( array( 'mint', array( 'kind' => 'site_default' ), array( 'kind' => 'core' ), array( 'kind' => 'custom', 'id' => 'example', 'version' => 'x' ), array( 'kind' => 'builtin', 'id' => 'mint' ), false, 1, new stdClass() ) as $reference ) { bad( $validator->reference( $reference ), 'Malformed reference rejected' ); }
ok( $validator->reference( 'legacy', 1 ), 'Legacy string denotes custom' );
bad( $resolver->resolve( custom( 'missing' ), $definitions ), 'Missing custom explicitly fails, no silent Daylight fallback' );
bad( $resolver->resolve( custom( 'bad' ), array( 'bad' => false ) ), 'Malformed stored custom explicitly fails' );
foreach ( array( 'example', 'legacy' ) as $id ) {
	$copy = ok( $operations->duplicate( custom( $id ), 'copy', 'Copy', $definitions ), 'Custom duplicate' );
	same( $definitions[ $id ]['colors'], $copy['colors'], 'Duplicate retains sparse overrides' );
	same( $definitions[ $id ]['density'], $copy['density'], 'Duplicate retains legacy metadata' );
	same( $definitions[ $id ]['description'], $copy['description'], 'Duplicate retains description' );
	$with_copy = $definitions; $with_copy['copy'] = $copy;
	$before = $resolver->resolve( custom( 'copy' ), $with_copy ); $with_copy[ $id ]['colors']['--h5p-theme-background'] = '#000';
	same( $before, $resolver->resolve( custom( 'copy' ), $with_copy ), 'No live custom-to-custom inheritance' );
}
foreach ( array_keys( $palettes ) as $id ) {
	$copy = ok( $operations->duplicate( builtin( $id ), 'copy', 'Copy' ), 'Builtin duplicate' );
	same( array(), $copy['colors'], 'Builtin duplicate uses zero overrides' );
	same( array( 'id' => $id, 'version' => Preset_Catalog::VERSION ), $copy['base_preset'], 'Builtin duplicate pins exact identity' );
	same( $palettes[ $id ]['colors'], ok( $resolver->resolve( custom( 'copy' ), array( 'copy' => $copy ) ), 'Builtin copy resolves' )['colors'], 'Duplicate palette parity' );
}
bad( $operations->duplicate( builtin( 'mint' ), 'example', 'Collision', $definitions ), 'Never overwrite existing destination' );
bad( $operations->duplicate( null, 'copy', 'Core' ), 'Core is not a builtin duplicate' );
foreach ( array( null, custom( 'missing' ), array( 'bad' => true ) ) as $global ) {
	$seed = $operations->create_from_default( $global, 'new_theme', 'New', $definitions );
	ok( $seed, 'Safe Add fallback' ); same( true, $seed['fallback'], 'Explicit creation fallback flag' ); same( 'daylight', $seed['data']['base_preset']['id'], 'Add fallback is Daylight' );
}
$seed = ok( $operations->create_from_default( custom( 'example' ), 'new_theme', 'New', $definitions ), 'Add from custom global' );
same( '', $seed['description'], 'New Add starts with blank description' );
foreach ( array( null, builtin( 'dark' ), custom( 'example' ), custom( 'legacy' ) ) as $reference ) {
	$baseline = $resolver->resolve( $reference, $definitions );
	foreach ( array( 'large', 'medium', 'small' ) as $density ) {
		$changed = $definitions; foreach ( $changed as &$d ) { $d['density'] = $density; } unset( $d );
		same( $baseline, $resolver->resolve( $reference, $changed ), 'Legacy density cannot change any resolved field' );
	}
	check( ! array_key_exists( 'density', $baseline['data'] ), 'Palette has no density profile' );
}

// Publication, global eligibility, tombstones, built-in protection and storage.
$state = Schema::initial(); ok( ( new Schema() )->validate( $state ), 'Versioned initial schema' );
same( 7, count( $publication->catalog( array(), $state['publication'] )['data'] ), 'Fresh defaults expose five builtins, Core and Site default' );
$state['publication']['themes']['custom:example'] = true; // A stale deleted-ID publication entry.
$state = ok( $changes->create( $state, $definitions['example'] ), 'Create custom' );
same( false, $state['publication']['themes']['custom:example'], 'Creation resets stale publication' );
check( ! isset( $publication->catalog( $state['definitions'], $state['publication'] )['data']['custom:example'] ), 'New custom hidden' );
ok( $publication->global_eligible( custom( 'example' ), $state['definitions'] ), 'Hidden custom can be global default' );
$global_state = ok( $changes->set_default( $state, custom( 'example' ) ), 'Select hidden global' );
same( 'example', $publication->resolve_learner( 'site_default', custom( 'example' ), $state['definitions'], $state['publication'] )['data']['id'], 'Site default can deliver hidden global' );
bad( $changes->delete( $global_state, custom( 'example' ) ), 'Selected global protected' );
bad( $changes->delete( $state, custom( 'example' ), array( custom( 'example' ) ) ), 'Effective global protected' );
$state = ok( $changes->publish( $state, 'custom:example', true ), 'Explicitly publish existing custom' );
check( isset( $publication->catalog( $state['definitions'], $state['publication'] )['data']['custom:example'] ), 'Published custom visible' );
$edited = $definitions['example']; $edited['label'] = 'Renamed';
$state = ok( $changes->replace( $state, custom( 'example' ), $edited ), 'Edit custom' );
same( true, $state['publication']['themes']['custom:example'], 'Edit preserves publication' );
foreach ( array_keys( $palettes ) as $id ) {
	bad( $changes->replace( $state, builtin( $id ), $edited ), 'Builtin edit protected' );
	bad( $changes->delete( $state, builtin( $id ) ), 'Builtin delete protected' );
}
foreach ( Publication_Policy::fixed_tokens() as $fixed ) { bad( $changes->publish( $state, $fixed, false ), 'Fixed choices cannot be hidden' ); }
bad( $changes->publish( $state, 'custom:missing', true ), 'Cannot publish missing custom' );
bad( $changes->publish( $state, 'builtin:unknown', true ), 'Cannot publish unknown builtin' );
bad( $changes->publish( $state, 'custom:example', 1 ), 'Publication requires Boolean' );
$deleted = ok( $changes->delete( $state, custom( 'example' ) ), 'Delete published nondefault custom' );
same( false, $deleted['publication']['themes']['custom:example'], 'Delete retains restrictive tombstone' );
same( false, ok( $changes->create( $deleted, $definitions['example'] ), 'Recreate' )['publication']['themes']['custom:example'], 'Recreation remains unpublished' );
foreach ( array( null, false, array(), array( 'schema_version' => 1, 'themes' => array( 'custom:example' => 1 ) ), array( 'schema_version' => 1, 'themes' => array( 'core' => false ) ) ) as $policy ) {
	same( Publication_Policy::fixed_tokens(), array_keys( $publication->catalog( $state['definitions'], $policy )['data'] ), 'Missing/malformed policy hides optional choices' );
}
$policy = $state['publication']; $policy['themes']['custom:missing'] = true;
check( ! isset( $publication->catalog( $state['definitions'], $policy )['data']['custom:missing'] ), 'Stale true cannot create a catalog entry' );
$state = ok( $changes->publish( $state, 'custom:example', false ), 'Unpublish' );
same( 'mint', $publication->resolve_learner( 'custom:example', builtin( 'mint' ), $state['definitions'], $state['publication'] )['data']['id'], 'Hidden preference follows global' );
same( 'core', $publication->resolve_learner( 'custom:missing', custom( 'gone' ), array(), $state['publication'] )['source'], 'Invalid personal and global safely use Core' );
same( array(), $publication->resolve_learner( 'core', builtin( 'mint' ), array(), $state['publication'] )['data']['colors'], 'Explicit Core never inherits global overrides' );
$broken = $state; $broken['settings']['default_theme'] = custom( 'gone' ); bad( ( new Schema() )->validate( $broken ), 'Invalid global state cannot be persisted' );
bad( $changes->delete( $broken, custom( 'example' ) ), 'Unverified global state blocks deletion' );
$store = new In_Memory_Store(); same( null, $store->read(), 'Absent store remains absent on read' );
check( $store->compare_and_swap( null, Schema::initial() ), 'Explicit in-memory initialization' );
check( ! $store->compare_and_swap( null, Schema::initial() ), 'No accidental reinitialization' );
$next = ok( $changes->create( $store->read(), $definitions['example'] ), 'Prepare atomic snapshot' );
$forged = $next; $forged['publication']['themes']['custom:example'] = true;
check( ! $store->compare_and_swap( 0, $forged ), 'Storage boundary also rejects raw auto-publication of new custom' );
check( $store->compare_and_swap( 0, $next ), 'Commit expected revision' );
$stored = $store->read(); check( ! $store->compare_and_swap( 0, $next ), 'Reject stale revision' ); same( $stored, $store->read(), 'Failed CAS leaves snapshot intact' );
$read = $store->read(); $read['definitions']['example']['label'] = 'Mutated'; same( $stored, $store->read(), 'Read returns detached values' );
check( ! $store->compare_and_swap( 1, $broken ), 'Reject invalid data before write' );
$forged_delete = $stored; ++$forged_delete['revision']; unset( $forged_delete['definitions']['example'], $forged_delete['publication']['themes']['custom:example'] );
check( ! $store->compare_and_swap( 1, $forged_delete ), 'Storage boundary requires deletion tombstone' );
foreach ( array( 'schema_version' => 2, 'revision' => '1', 'settings' => null, 'definitions' => false, 'publication' => null ) as $field => $value ) {
	$invalid = Schema::initial(); $invalid[ $field ] = $value; bad( ( new Schema() )->validate( $invalid ), 'Malformed storage field' );
}
$invalid = Schema::initial(); $invalid['unknown'] = true; bad( ( new Schema() )->validate( $invalid ), 'Unknown envelope field rejected' );
$exhausted = Schema::initial(); $exhausted['revision'] = PHP_INT_MAX;
bad( $changes->create( $exhausted, definition() ), 'Revision overflow fails before mutation' );
$same_id = ok( $changes->create( Schema::initial(), definition( 'mint' ) ), 'Typed custom ID may match a builtin ID' );
same( 'builtin', $resolver->resolve( builtin( 'mint' ), $same_id['definitions'] )['data']['kind'], 'Custom cannot shadow immutable builtin identity' );

// Compare against the original, unmodified Backdrop source (optional explicit path).
if ( isset( $argv[1] ) ) {
	$source = realpath( $argv[1] );
	if ( ! $source ) { throw new RuntimeException( 'Backdrop source root is unavailable' ); }
	require __DIR__ . '/backdrop-memory.php';
	require $source . '/h5p_themer.module';
	require $source . '/h5p_themer.admin.inc';
	$before_parity = $count;
	same( $palettes, h5p_themer_presets( Preset_Catalog::VERSION )['data'], 'Exact full preset catalog parity, including metadata/provenance' );
	same( Color_Properties::supported(), h5p_themer_color_properties(), 'Exact supported-property parity' );
	same( Color_Properties::groups(), _h5p_themer_admin_color_groups(), 'Exact property/group definition parity' );
	foreach ( $palettes as $id => $preset ) { foreach ( $preset['colors'] as $key => $value ) { same( $value, h5p_themer_preset_load( $id, Preset_Catalog::VERSION )['data']['colors'][ $key ], 'Exact palette value parity' ); } }
	$cases = array( definition(), definition( 'example', 1 ) );
	foreach ( Color_Properties::supported() as $property ) {
		foreach ( array_merge( $colors, $invalid_colors ) as $color ) { $d = definition(); $d['colors'][ $property ] = $color; $cases[] = $d; }
	}
	foreach ( array( 'schema_version', 'id', 'label', 'density', 'colors', 'base_preset' ) as $field ) { $d = definition(); unset( $d[ $field ] ); $cases[] = $d; }
	foreach ( array( 'schema_version' => '2', 'id' => 'other', 'label' => '', 'density' => 'compact', 'colors' => false, 'base_preset' => custom( 'example' ), 'unknown' => true ) as $field => $value ) { $d = definition(); $d[ $field ] = $value; $cases[] = $d; }
	foreach ( array( '', str_repeat( 'x', 255 ), str_repeat( 'x', 256 ), "bad\n", "\xFF" ) as $label ) { $d = definition(); $d['label'] = $label; $cases[] = $d; }
	foreach ( array( '', str_repeat( 'x', 4096 ), str_repeat( 'x', 4097 ), "bad\0", "line\ntext\t", null ) as $description ) { $d = definition(); $d['description'] = $description; $cases[] = $d; }
	foreach ( array( '', 'A', str_repeat( 'a', 65 ), 'é', null, array() ) as $id ) { same( h5p_themer_valid_theme_id( $id ), Theme_Validator::valid_id( $id ), 'Identifier parity' ); }
	foreach ( $cases as $case ) {
		$old = h5p_themer_config_errors( 'h5p_themer.theme.example', $case );
		same( empty( $old ), empty( $validator->definition( 'example', $case )['errors'] ), 'Definition admission parity' );
	}
	pjh5pwp_backdrop_snapshot( $definitions, builtin( 'mint' ), $state['publication'] );
	foreach ( array_merge( $references, array( false, array( 'kind' => 'site_default' ) ) ) as $reference ) {
		$old = h5p_themer_selection_reference( $reference ); $new = $validator->reference( $reference );
		same( empty( $old['errors'] ), empty( $new['errors'] ), 'Reference shape parity' );
		if ( ! $old['errors'] ) { same( $old['data'], $new['data'], 'Typed reference parity' ); }
	}
	foreach ( array_merge( array( null, custom( 'example' ), custom( 'legacy' ), custom( 'missing' ) ), array_map( 'builtin', array_keys( $palettes ) ) ) as $reference ) {
		$old = h5p_themer_selection_resolve( $reference ); $new = $resolver->resolve( $reference, $definitions );
		same( empty( $old['errors'] ), empty( $new['errors'] ), 'Resolution admission parity' );
		if ( ! $old['errors'] ) { unset( $old['data']['density'] ); same( $old['data'], $new['data'], 'Resolved data parity except deliberately excluded legacy density' ); }
	}
	foreach ( array( null, builtin( 'dark' ), custom( 'example' ), custom( 'legacy' ), custom( 'missing' ) ) as $global ) {
		pjh5pwp_backdrop_snapshot( $definitions, $global, $state['publication'] );
		$old = _h5p_themer_admin_add_snapshot(); $new = $operations->create_from_default( $global, 'copy', 'New', $definitions );
		$seed = $old['seed']; $seed['id'] = 'copy'; $seed['label'] = 'New';
		$actual_seed = $new['data']; ksort( $seed ); ksort( $actual_seed );
		same( $seed, $actual_seed, 'Add seed parity (pins, sparse overrides, metadata)' ); same( $old['fallback'], $new['fallback'], 'Add fallback parity' );
	}
	$policies = array( Schema::initial()['publication'], array( 'schema_version' => 1, 'themes' => array() ),
		array( 'schema_version' => 1, 'themes' => array( 'custom:example' => true, 'builtin:dark' => false, 'custom:gone' => true ) ),
		array( 'schema_version' => 1, 'themes' => array( 'core' => false ) ) );
	foreach ( $policies as $policy ) {
		pjh5pwp_backdrop_snapshot( $definitions, null, $policy );
		same( empty( h5p_themer_visibility_errors( $policy ) ), empty( $publication->validate( $policy )['errors'] ), 'Publication validation parity' );
		same( h5p_themer_learner_catalog()['data'], $publication->catalog( $definitions, $policy )['data'], 'Initialized/blocked learner catalog parity' );
	}
	pjh5pwp_backdrop_snapshot( array(), null, null );
	same( _h5p_themer_visibility_capture( 'fresh' )['policy'], Schema::initial()['publication'], 'Fresh publication seed parity' );
	foreach ( array( null, builtin( 'mint' ), custom( 'example' ), custom( 'legacy' ) ) as $global ) {
		pjh5pwp_backdrop_snapshot( $definitions, $global, Schema::initial()['publication'] );
		$old_blocks = false;
		try { _h5p_themer_validate_theme_deletion( new Config( 'h5p_themer.theme.example' ), null ); }
		catch ( ConfigValidateException $error ) { $old_blocks = true; }
		same( $old_blocks, ! empty( $publication->deletion_errors( custom( 'example' ), $definitions, array( $global ) ) ), 'Global-default deletion protection parity' );
		same( empty( h5p_themer_selection_resolve( $global )['errors'] ), empty( $publication->global_eligible( $global, $definitions )['errors'] ), 'Global eligibility independent of publication' );
	}
	$parity = $count - $before_parity;
}
echo 'PASS Phase 1B: ' . $count . ' assertions (' . $parity . ' Backdrop parity assertions); no CMS bootstrap or persistence.' . PHP_EOL;
