<?php
/** Test fixture only; no H5P implementation. */
class H5PCore {
	public static $coreApi = array( 'majorVersion' => 1, 'minorVersion' => 28 );
	// Only the canonical DIV used by the real-policy attachment fixture is supported.
	public static function determineEmbedType( $embed, $allowed ) { return 'div' === $embed && 'div' === $allowed ? 'div' : null; }
	public function getDependenciesFiles( $dependencies, $prefix = '' ) { throw new RuntimeException( 'No aggregation' ); }
	public function getAssetsUrls( $assets ) { throw new RuntimeException( 'No URL construction' ); }
}
