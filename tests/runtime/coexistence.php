<?php
/** Real Runtime_Policy/detector exercised through isolated foundation fixtures. */
if ( 'cli' !== PHP_SAPI ) { exit; }
if ( '--worker' !== ( $argv[1] ?? null ) ) {
	$total = 0;
	foreach ( array( 'compatible', 'snordian-active', 'snordian-constant', 'snordian-namespace', 'snordian-priority-zero', 'missing', 'wrong-api', 'multisite' ) as $signal ) {
		$p = proc_open( array( PHP_BINARY, '-n', __FILE__, '--worker', $signal ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		if ( ! is_resource( $p ) ) { throw new RuntimeException( 'Cannot start runtime fixture' ); }
		fclose( $pipes[0] ); $out = stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
		$err = stream_get_contents( $pipes[2] ); fclose( $pipes[2] ); $exit = proc_close( $p );
		if ( $exit || '' !== $err || ! ctype_digit( trim( $out ) ) ) { throw new RuntimeException( $signal . ': ' . $out . $err ); }
		$total += (int) $out;
	}
	echo 'Phase 2B-2A runtime coexistence: 8 isolated scenarios; ' . $total . " assertions passed.\n"; exit;
}
$signal = $argv[2]; $argv = array( __FILE__, $signal );
ob_start(); require dirname( __DIR__ ) . '/worker.php'; ob_end_clean();
$options = $GLOBALS['pjh5pwp_options']; $hooks = $GLOBALS['pjh5pwp_hooks']; $network = $GLOBALS['pjh5pwp_network'];
require dirname( __DIR__, 2 ) . '/includes/runtime.php';
$count = 0; $calls = 0;
function verify_runtime( $condition ) { global $count; ++$count; if ( ! $condition ) { throw new RuntimeException( 'Runtime coexistence assertion failed' ); } }
$service = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Service( array( $policy, 'evaluate' ), static function () use ( &$calls ) {
	++$calls;
	$r = ( new \Papijo\H5PThemerWordPress\Theme\Palette_Resolver() )->resolve( array( 'kind' => 'builtin', 'id' => 'lavender', 'version' => \Papijo\H5PThemerWordPress\Theme\Preset_Catalog::VERSION ) );
	return array( 'theme' => $r + array( 'source' => 'personal' ), 'density' => 'large' );
} );
$d = $service->describe( 1 );
if ( 'compatible' === $signal ) {
	verify_runtime( $d['admission']['permitted'] && '' !== $d['color_declarations'] && 'large' === $d['density']['selection'] );
	// A previously admitted service must immediately stop on a newly active conflict.
	$GLOBALS['pjh5pwp_options']['active_plugins'][] = 'snordians-h5p-themer/snordians-h5p-themer.php';
	$d = $service->describe( 1 ); verify_runtime( 'snordian_active' === $d['admission']['reason'] );
	verify_runtime( 1 === $calls ); $GLOBALS['pjh5pwp_options'] = $options;
} else {
	verify_runtime( 0 === $calls );
	if ( 0 === strpos( $signal, 'snordian-' ) ) { verify_runtime( 'snordian_active' === $d['admission']['reason'] ); }
}
verify_runtime( ! $d['admission']['permitted'] ); verify_runtime( null === $d['theme'] );
verify_runtime( '' === $d['color_declarations'] ); verify_runtime( null === $d['density'] );
verify_runtime( $options === $GLOBALS['pjh5pwp_options'] ); verify_runtime( $hooks === $GLOBALS['pjh5pwp_hooks'] ); verify_runtime( $network === $GLOBALS['pjh5pwp_network'] );
echo $count;
