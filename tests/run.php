<?php
/** Run deterministic isolated CLI checks. No WordPress installation is loaded. */
if ( 'cli' !== PHP_SAPI ) { exit; }
$scenarios = array( 'missing', 'inactive', 'active-unloaded', 'compatible', 'wrong-api', 'malformed-api', 'missing-method', 'missing-hooks', 'wrong-reference', 'foreign-classes', 'autoload-failure', 'snordian-active', 'snordian-constant', 'snordian-namespace', 'snordian-priority-zero', 'late-conflict', 'multisite' );
$actual_root = isset( $argv[1] ) ? $argv[1] : null;
if ( $actual_root ) { $scenarios[] = 'actual-integration'; }
$checks = 0;
foreach ( $scenarios as $scenario ) {
	$command = array( PHP_BINARY, '-n', '-d', 'display_errors=stderr', __DIR__ . '/worker.php', $scenario );
	if ( 'actual-integration' === $scenario ) { $command[] = $actual_root; }
	$process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, dirname( __DIR__ ) );
	if ( ! is_resource( $process ) ) { throw new RuntimeException( 'Cannot start test worker' ); }
	fclose( $pipes[0] );
	$output = stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
	$error = stream_get_contents( $pipes[2] ); fclose( $pipes[2] );
	$exit = proc_close( $process );
	$result = json_decode( $output, true );
	if ( 0 !== $exit || '' !== $error || ! is_array( $result ) ) {
		fwrite( STDERR, 'FAIL ' . $scenario . PHP_EOL . $output . $error ); exit( 1 );
	}
	$checks += $result['checks'];
	echo 'PASS ' . $scenario . ' (' . $result['checks'] . ')' . PHP_EOL;
}
echo 'PASS: ' . count( $scenarios ) . ' scenarios; ' . $checks . ' assertions; no live activation or site bootstrap.' . PHP_EOL;
