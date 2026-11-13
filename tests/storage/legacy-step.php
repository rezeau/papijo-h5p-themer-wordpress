<?php
/** Synthetic migration fixture ONLY. No production v0 schema ever shipped. */
namespace Papijo\H5PThemerWordPress\Tests;
use Papijo\H5PThemerWordPress\Storage\Migration_Step;
use Papijo\H5PThemerWordPress\Storage\Schema;

final class Legacy_Step implements Migration_Step {
	public $fail = false;
	public function from_version() { return 0; }
	public function to_version() { return 1; }
	public function validate_source( $snapshot ) {
		if ( ! is_array( $snapshot ) || ! isset( $snapshot['schema_version'] ) || 0 !== $snapshot['schema_version'] ) { return array( 'data' => null, 'errors' => array( 'invalid_fixture' ) ); }
		$projected = $snapshot; $projected['schema_version'] = 1;
		$valid = ( new Schema() )->validate( $projected );
		return array( 'data' => $valid['errors'] ? null : $snapshot, 'errors' => $valid['errors'] );
	}
	public function convert( array $snapshot ) {
		if ( $this->fail ) { throw new \RuntimeException( 'Injected migration failure' ); }
		$snapshot['schema_version'] = 1;
		return $snapshot;
	}
}
