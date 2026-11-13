<?php
/** Isolated colors-only transfer checks. No CMS, filesystem writes or database. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-transfer.php';
use Papijo\H5PThemerWordPress\Transfer\Theme_JSON;
use Papijo\H5PThemerWordPress\Transfer\JSON_Decoder;
use Papijo\H5PThemerWordPress\Transfer\Import_Planner;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Validator;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Storage\Schema;
$checks = 0;
function check( $value, $message ) { global $checks; if ( ! $value ) { throw new RuntimeException( $message ); } ++$checks; }
function ok( $result ) { check( array() === $result['errors'], 'Success: ' . implode( ',', $result['errors'] ) ); return $result['data']; }
function bad( $result ) { check( null === $result['data'] && ! empty( $result['errors'] ), 'Atomic rejection without partial output' ); }
function record( $id = 'ocean' ) { return array( 'id' => $id, 'label' => '  Été <palette>  ', 'description' => "Line one\nLine two\t", 'base_preset' => array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ), 'colors' => array( '--h5p-theme-background' => '#aBc', '--h5p-theme-focus' => '#123456' ) ); }
function wire( array $records ) {
	foreach ( $records as &$r ) { $r['colors'] = (object) $r['colors']; } unset( $r );
	return json_encode( array( 'format' => Theme_JSON::FORMAT, 'format_version' => 1, 'themes' => $records ) );
}
function native( $schema = 2 ) { $r = record(); $r['_config_name'] = 'h5p_themer.theme.ocean'; $r['schema_version'] = $schema; $r['density'] = 'large'; $r['colors'] = (object) $r['colors']; if ( 1 === $schema ) { unset( $r['base_preset'] ); } return $r; }
$codec = new Theme_JSON(); $planner = new Import_Planner(); $decoder = new JSON_Decoder();
$one = record(); $two = record( 'amber' ); $two['base_preset'] = null; $two['colors'] = array(); unset( $two['description'] );
$json = ok( $codec->serialize( array( $one ) ) );
check( array( $one ) === ok( $codec->parse( $json ) ), 'Single exact value round trip' );
$multi = ok( $codec->serialize( array( $one, $two ) ) );
check( array( $two, $one ) === ok( $codec->parse( $multi ) ), 'Multiple records, Core inheritance, empty object and optional description' );
$reordered = $one; $reordered['colors'] = array_reverse( $one['colors'], true ); $reordered['base_preset'] = array_reverse( $one['base_preset'], true ); $reordered = array_reverse( $reordered, true );
check( $multi === ok( $codec->serialize( array( $two, $reordered ) ) ), 'Deterministic record/property/field ordering' );
check( false === strpos( $multi, 'density' ) && false !== strpos( $multi, '"colors": {}' ), 'Density excluded and empty maps encoded as objects' );
$state = Schema::initial(); $before = serialize( $state );
$profiles = array( 'theme' => 'site_default', 'density' => 'small', 'allow_density' => '1' ); $roles = array( 'subscriber' => true ); $foreign = serialize( array( $profiles, $roles ) );
$plan = ok( $planner->prepare( $multi, $state ) );
check( false === $plan['directly_persistable'] && 'detached_color_import_plan' === $plan['kind'], 'Explicit detached plan marker' );
check( $plan['expected_revision'] === 0 && $plan['collisions']['conflicts'] === array(), 'Revision and successful collision results' );
check( $plan['publication_intents'] === array( 'custom:amber' => false, 'custom:ocean' => false ), 'All new customs unpublished' );
check( $before === serialize( $state ) && $foreign === serialize( array( $profiles, $roles ) ), 'No global/profile/role/settings mutation' );
bad( ( new Schema() )->validate( $plan ) );
bad( ( new Theme_Validator() )->definition( 'ocean', $plan['records'][1] ) );
bad( ( new Library_Changes() )->create( $state, $plan['records'][1] ) );
foreach ( array( 1, 2 ) as $schema ) {
	foreach ( array( 'small', 'medium', 'large' ) as $density ) {
		$n = native( $schema ); $n['density'] = $density; $n['_config_static'] = true; $saved = serialize( $n );
		$parsed = ok( $codec->parse( json_encode( $n ) ) ); $expected = $one; if ( 1 === $schema ) { $expected['base_preset'] = null; }
		check( $parsed === array( $expected ), 'Native metadata discarded; colors/inheritance preserved' );
		$p = ok( $planner->prepare( json_encode( $n ), $state ) );
		check( $p['records'] === $parsed && false === strpos( json_encode( $p['records'] ), 'density' ), 'Native Density never enters plan records' );
		check( $saved === serialize( $n ), 'Native input is not mutated' );
		check( false === strpos( ok( $codec->serialize( $parsed ) ), 'density' ), 'Native re-export remains colors-only' );
	}
}
$n = native(); $n['colors'] = array(); ok( $codec->parse( json_encode( $n ) ) );
$n['_config_static'] = false; ok( $codec->parse( json_encode( $n ) ) );
foreach ( array( null, '', 'wide', 'Large', 1, true, array(), (object) array() ) as $value ) { $n = native(); $n['density'] = $value; bad( $codec->parse( json_encode( $n ) ) ); }
foreach ( array( 'density', 'id', '_config_name', 'label', 'colors', 'schema_version', 'base_preset' ) as $key ) { $n = native(); unset( $n[$key] ); bad( $codec->parse( json_encode( $n ) ) ); }
foreach ( array( array( 'schema_version', 3 ), array( 'schema_version', '2' ), array( '_config_name', 'h5p_themer.settings' ), array( 'id', 'other' ), array( '_config_static', 1 ), array( 'colors', array( '#123' ) ), array( 'format', Theme_JSON::FORMAT ), array( 'published', true ) ) as $change ) { $n = native(); $n[$change[0]] = $change[1]; bad( $codec->parse( json_encode( $n ) ) ); }
$n = native( 1 ); $n['base_preset'] = null; bad( $codec->parse( json_encode( $n ) ) );
foreach ( array( '', 'A', '../x', str_repeat( 'a', 65 ), null ) as $value ) { $r = $one; $r['id'] = $value; bad( $codec->parse( wire( array( $r ) ) ) ); }
foreach ( array( '', str_repeat( 'x', 256 ), "bad\n", null, array() ) as $value ) { $r = $one; $r['label'] = $value; bad( $codec->parse( wire( array( $r ) ) ) ); }
foreach ( array( str_repeat( 'x', 4097 ), "bad\x01", null, array() ) as $value ) { $r = $one; $r['description'] = $value; bad( $codec->parse( wire( array( $r ) ) ) ); }
foreach ( array( '--evil', '--h5p-theme-contrast-cta-dark', '--h5p-theme-font-size-m' ) as $property ) { $r = $one; $r['colors'] = array( $property => '#fff' ); bad( $codec->parse( wire( array( $r ) ) ) ); }
foreach ( array( 'red', 'var(--x)', 'url(x)', '#abcd', ' #fff', '', null, array() ) as $value ) { $r = $one; $r['colors']['--h5p-theme-background'] = $value; bad( $codec->parse( wire( array( $r ) ) ) ); }
foreach ( array( array( 'id' => 'unknown', 'version' => Preset_Catalog::VERSION ), array( 'id' => 'mint', 'version' => 'latest' ), array(), 'mint', array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION, 'parent' => 'x' ) ) as $base ) { $r = $one; $r['base_preset'] = $base; bad( $codec->parse( wire( array( $r ) ) ) ); }
foreach ( array( 'density', 'default_theme', 'publication', 'runtime_enabled', '_config_name', 'schema_version' ) as $extra ) { $r = $one; $r[$extra] = 'large'; bad( $codec->parse( wire( array( $r ) ) ) ); bad( $codec->serialize( array( $r ) ) ); }
$r = $one; $r['label'] = str_repeat( 'x', 255 ); $r['description'] = str_repeat( 'x', 4096 ); ok( $codec->parse( wire( array( $r ) ) ) );
foreach ( array( '', '{', 'null', 'true', '42', '[]', '{}', '{"format":1}', '{"a":1,}', "{\"x\":\"\xFF\"}", $json . 'null', "\xEF\xBB\xBF" . $json ) as $raw ) { bad( $codec->parse( $raw ) ); }
foreach ( array( '{"x":1,"x":2}', '{"x":1,"\u0078":2}', '{"a":{"b":1,"b":2}}', '[{"x":1,"x":2}]' ) as $raw ) { $failure = $decoder->decode( $raw ); bad( $failure ); check( array( 'duplicate_json_member' ) === $failure['errors'], 'Duplicate decoded members rejected' ); }
foreach ( array( '{"x":1,"a":{"x":2}}', '[{"x":1},{"x":2}]', '"quote \\" and braces {}"', 'null', '1', '[]', '{}' ) as $raw ) { ok( $decoder->decode( $raw ) ); }
bad( $codec->parse( str_repeat( ' ', JSON_Decoder::MAX_BYTES + 1 ) ) );
ok( $decoder->decode( 'null' . str_repeat( ' ', JSON_Decoder::MAX_BYTES - 4 ) ) );
ok( $decoder->decode( str_repeat( '[', 14 ) . '0' . str_repeat( ']', 14 ) ) );
bad( $codec->parse( str_repeat( '[', 20 ) . '0' . str_repeat( ']', 20 ) ) );
$d = json_decode( $json, true );
foreach ( array( 0, 2, '1', 1.5, null ) as $version ) { $d['format_version'] = $version; bad( $codec->parse( json_encode( $d ) ) ); }
bad( $codec->parse( wire( array( $one, $one ) ) ) ); bad( $codec->serialize( array( $one, $one ) ) );
bad( $codec->parse( str_replace( '"colors": {}', '"colors": []', $multi ) ) );
bad( $codec->parse( wire( array() ) ) ); bad( $codec->serialize( array() ) );
$many = array(); for ( $i = 0; $i < 100; ++$i ) { $many[] = record( 'theme_' . $i ); }
ok( $codec->serialize( $many ) ); $many[] = record( 'extra' ); bad( $codec->serialize( $many ) ); bad( $codec->parse( wire( $many ) ) );
$bulky = array();
for ( $i = 0; $i < 100; ++$i ) { $r = record( 'large_' . $i ); $r['description'] = str_repeat( "\t", 4096 ); $r['colors'] = array_fill_keys( \Papijo\H5PThemerWordPress\Theme\Color_Properties::supported(), '#123456' ); $bulky[] = $r; }
$bulky_json = ok( $codec->serialize( $bulky ) );
check( strlen( $bulky_json ) <= JSON_Decoder::MAX_BYTES, 'Large valid export includes encoding overhead within byte limit' );
$r = $one; $r['colors']['--h5p-theme-background'] = 'red'; bad( $planner->prepare( wire( array( $two, $r ) ), $state ) );
$stored = $one; $stored['schema_version'] = 2; $stored['density'] = 'small';
$state = ok( ( new Library_Changes() )->create( $state, $stored ) );
$state = ok( ( new Library_Changes() )->set_default( $state, array( 'kind' => 'custom', 'id' => 'ocean' ) ) ); $before = serialize( $state );
bad( $planner->prepare( $json, $state ) ); bad( $planner->prepare( $multi, $state ) );
check( $before === serialize( $state ), 'Collision preserves default, existing definitions and publication' );
$exported = ok( $codec->export( $state, array( 'ocean' ) ) ); $changed = $state; $changed['definitions']['ocean']['density'] = 'large';
check( $exported === ok( $codec->export( $changed, array( 'ocean' ) ) ), 'Legacy stored Density has no effect on exports' );
bad( $codec->export( $state, array( 'builtin:mint' ) ) ); bad( $codec->export( $state, array( 'mint' ) ) );
$mint = record( 'mint' ); $p = ok( $planner->prepare( wire( array( $mint ) ), $state ) );
check( $p['publication_intents'] === array( 'custom:mint' => false ), 'Built-in ID remains a distinct unpublished custom identity' );
$state['publication']['themes']['custom:mint'] = true; $before = serialize( $state );
$p = ok( $planner->prepare( wire( array( $mint ) ), $state ) ); check( false === $p['publication_intents']['custom:mint'] && $before === serialize( $state ), 'Stale publication token resets only as detached intent' );
$legacy = $state; $legacy['definitions']['ocean']['schema_version'] = 1; unset( $legacy['definitions']['ocean']['base_preset'] );
check( null === ok( $codec->parse( ok( $codec->export( $legacy, array( 'ocean' ) ) ) ) )[0]['base_preset'], 'Legacy sparse Core export stays unpinned' );
$max = $state; $max['revision'] = PHP_INT_MAX; bad( $planner->prepare( wire( array( $mint ) ), $max ) );
$broken = $state; $broken['settings']['default_theme'] = array( 'kind' => 'custom', 'id' => 'missing' ); bad( $planner->prepare( wire( array( $mint ) ), $broken ) );
check( $foreign === serialize( array( $profiles, $roles ) ), 'All operations preserve independent preferences/permissions' );
check( $one === record(), 'Canonical input remains unchanged after serialization and validation' );
echo 'PASS transfer: ' . $checks . " assertions; detached plans only, no CMS or persistence.\n";
