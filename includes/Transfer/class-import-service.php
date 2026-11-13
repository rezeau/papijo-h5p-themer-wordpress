<?php
/** Authorized prepare/commit boundary. No HTTP endpoint or schema migration. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Snapshot_Codec;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;

final class Import_Service {
	private $store;
	private $authorize;
	private $pending;
	public function __construct( Snapshot_Store $store, ?callable $authorize = null ) {
		$this->store = $store;
		$this->authorize = $authorize ?: static function () {
			return function_exists( 'current_user_can' ) && function_exists( 'is_multisite' ) &&
				current_user_can( 'manage_options' ) && ! is_multisite();
		};
	}
	private function allowed() {
		try { return true === call_user_func( $this->authorize ); }
		catch ( \Throwable $error ) { return false; }
	}
	/** Retain one server-owned review context. Returned detached plan is unchanged. */
	public function prepare( $json ) {
		$this->pending = null;
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		// Reject malformed/oversized input before touching the repository.
		$parsed = ( new Theme_JSON() )->parse( $json );
		if ( $parsed['errors'] ) { return $this->error( 'invalid_import_input' ); }
		try { $snapshot = $this->store->read(); }
		catch ( \Throwable $error ) { return $this->error( 'import_storage_unavailable' ); }
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		$plan = ( new Import_Planner() )->prepare( $json, $snapshot );
		if ( $plan['errors'] ) { return $this->error( $plan['errors'] ); }
		$this->pending = array( 'json' => $json, 'plan' => $plan['data'] );
		return $plan + array( 'outcome' => 'not_committed' );
	}
	/** Only the exact plan issued by this instance can be committed, once. */
	public function commit( $plan ) {
		$pending = $this->pending;
		$this->pending = null; // Consume even on error; never retry/rebase automatically.
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		if ( null === $pending || ! is_array( $plan ) || $pending['plan'] !== $plan ) { return $this->error( 'invalid_import_plan' ); }
		try { $snapshot = $this->store->read(); }
		catch ( \Throwable $error ) { return $this->error( 'import_storage_unavailable' ); }
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		// Reparse ORIGINAL bounded bytes and recompute every invariant on current data.
		$current = ( new Import_Planner() )->prepare( $pending['json'], $snapshot );
		if ( $current['errors'] || $current['data'] !== $plan ) { return $this->error( 'storage_conflict' ); }
		$definitions = array();
		foreach ( $current['data']['records'] as $record ) {
			$definition = ( new Import_Storage_Compatibility() )->definition( $record );
			if ( $definition['errors'] ) { return $this->error( 'invalid_import_plan' ); }
			$definitions[] = $definition['data'];
		}
		$next = ( new Library_Changes() )->create_many( $snapshot, $definitions );
		if ( $next['errors'] ) { return $this->error( $next['errors'] ); }
		try { ( new Snapshot_Codec() )->encode( $next['data'] ); }
		catch ( \Throwable $error ) { return $this->error( 'import_storage_limit' ); }
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		try {
			if ( ! $this->store->compare_and_swap( $plan['expected_revision'], $next['data'] ) ) { return $this->error( 'storage_conflict' ); }
		} catch ( Storage_Exception $error ) {
			$outcome = $error->outcome();
			return $this->error( 'committed' === $outcome ? 'import_committed_cache_failed' : ( 'unknown' === $outcome ? 'import_write_unknown' : 'import_write_failed' ), $outcome );
		} catch ( \Throwable $error ) { return $this->error( 'import_write_unknown', 'unknown' ); }
		return array( 'data' => array( 'ids' => array_column( $definitions, 'id' ), 'count' => count( $definitions ), 'revision' => $next['data']['revision'] ), 'errors' => array(), 'outcome' => 'committed' );
	}
	private function error( $codes, $outcome = 'not_committed' ) { return array( 'data' => null, 'errors' => (array) $codes, 'outcome' => $outcome ); }
}
