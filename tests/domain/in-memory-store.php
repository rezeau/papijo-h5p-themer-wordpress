<?php
/** Test-only storage. Never calls WordPress or persists to disk. */
namespace Papijo\H5PThemerWordPress\Tests;

use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Schema;

final class In_Memory_Store implements Snapshot_Store {
	private $snapshot;
	public function read() { return $this->snapshot; }
	public function compare_and_swap( $expected_revision, array $next ) {
		if ( null !== $expected_revision && ( ! is_int( $expected_revision ) || $expected_revision < 0 || $expected_revision === PHP_INT_MAX ) ) { return false; }
		$current = null === $this->snapshot ? null : $this->snapshot['revision'];
		$next_revision = null === $expected_revision ? 0 : $expected_revision + 1;
		if ( $expected_revision !== $current || ( new Schema() )->transition( $this->snapshot, $next )['errors'] || $next['revision'] !== $next_revision ) { return false; }
		$this->snapshot = $next;
		return true;
	}
}
