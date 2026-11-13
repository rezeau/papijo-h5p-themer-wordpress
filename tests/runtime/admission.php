<?php
/** Transport through the real policy and all detector signals. Isolated processes only. */
if ( 'cli' !== PHP_SAPI ) { exit; }
if ( '--worker' !== ( $argv[1] ?? null ) ) {
	$total = 0;
	foreach ( array( 'compatible', 'missing', 'inactive', 'wrong-api', 'malformed-api', 'multisite', 'snordian-active', 'snordian-constant', 'snordian-namespace', 'snordian-priority-zero' ) as $signal ) {
		$p = proc_open( array( PHP_BINARY, '-n', __FILE__, '--worker', $signal ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		if ( ! is_resource( $p ) ) { throw new RuntimeException( 'Cannot start admission fixture' ); }
		fclose( $pipes[0] ); $out = stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
		$err = stream_get_contents( $pipes[2] ); fclose( $pipes[2] ); $exit = proc_close( $p );
		if ( $exit || $err || ! ctype_digit( trim( $out ) ) ) { throw new RuntimeException( $signal . ': ' . $out . $err ); }
		$total += (int) $out;
	}
	echo 'Phase 2B-2B admission: 10 isolated scenarios; ' . $total . " assertions passed.\n"; exit;
}
$signal = $argv[2]; $argv = array( __FILE__, $signal );
ob_start(); require dirname( __DIR__ ) . '/worker.php'; ob_end_clean();
$count = 0; $effective_calls = 0;
function admission_check( $value ) { global $count; ++$count; if ( ! $value ) { throw new RuntimeException( 'Admission/transport failed' ); } }
$before = array( $GLOBALS['pjh5pwp_options'], $GLOBALS['pjh5pwp_network'], $GLOBALS['pjh5pwp_hooks'] );
$gate = new \Papijo\H5PThemerWordPress\Runtime\Runtime_Admission( array( $policy, 'evaluate' ) );
function admin_url( $path ) { return 'https://wp-test/wp-admin/' . $path; }
function home_url( $path ) { return 'https://wp-test' . $path; }
$registry = new \Papijo\H5PThemerWordPress\Runtime\Playback_Registry( $gate, static function () { return true; } );
$content = array( 'id' => 10, 'embedType' => 'div', 'library' => array( 'id' => 2, 'name' => 'H5P.Accordion', 'majorVersion' => 1, 'minorVersion' => 0, 'embedTypes' => 'div' ) );
$registry->observe( '<div class="h5p-content" data-content-id="10"></div>', $content );
$attachment = new \Papijo\H5PThemerWordPress\Runtime\Theme_Attachment( $registry, static function () { return true; } );
admission_check( ( 'compatible' === $signal ) === ( null !== $attachment->manifest() ) );
$service = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Service( static function () use ( $gate ) { return $gate->evaluate( true ); }, static function () use ( &$effective_calls ) {
	++$effective_calls; $resolved = ( new \Papijo\H5PThemerWordPress\Theme\Palette_Resolver() )->resolve( null );
	return array( 'theme' => $resolved + array( 'source' => 'global' ), 'density' => 'original' );
} );
$endpoint = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint( $service, static function () { return 0; } );
$server = array( 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'https://wp-test', 'HTTP_REFERER' => 'https://wp-test/lesson/', 'HTTP_X_PJH5PWP_REQUEST' => 'appearance-v1' );
$post = array( 'action' => 'pjh5pwp_appearance', 'request_id' => str_repeat( 'a', 32 ) );
$request = static function () use ( $endpoint, $server, $post ) { return $endpoint->respond( $server, array(), $post, 'https://wp-test/', 'https://wp-test/wp-admin/' )['body']['appearance']; };
$d = $request();
admission_check( ( 'compatible' === $signal ) === $d['admission']['permitted'] );
if ( 'compatible' === $signal ) {
	admission_check( 1 === $effective_calls );
	$GLOBALS['pjh5pwp_options']['active_plugins'][] = 'snordians-h5p-themer/snordians-h5p-themer.php';
	$d = $request(); admission_check( 'snordian_active' === $d['admission']['reason'] );
	admission_check( null === $attachment->manifest() );
	array_pop( $GLOBALS['pjh5pwp_options']['active_plugins'] );
	admission_check( 1 === $effective_calls );
} else { admission_check( 0 === $effective_calls ); }
admission_check( null === $d['theme'] && '' === $d['color_declarations'] && null === $d['density'] );
admission_check( ! $gate->evaluate( false )['future_runtime_permitted'] );
admission_check( true === $policy->evaluate()['runtime_enabled'] && true === $policy->evaluate()['runtime_implemented'] );
admission_check( $before === array( $GLOBALS['pjh5pwp_options'], $GLOBALS['pjh5pwp_network'], $GLOBALS['pjh5pwp_hooks'] ) );
echo $count;
