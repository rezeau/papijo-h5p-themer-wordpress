<?php
/** Explicit initialization/upgrades. Construction and status checks never write. */
namespace Papijo\H5PThemerWordPress\Storage;

/** @license GPL-3.0-or-later */
final class Storage_Installation {
	private $store;
	private $migrations;
	public function __construct( Snapshot_Store $store, ?Schema_Migrations $migrations = null ) {
		$this->store = $store;
		$this->migrations = $migrations ?: new Schema_Migrations();
	}
	public function status() {
		try {
			$raw = $this->store->read();
			if ( null === $raw ) { return $this->result( 'absent', null ); }
			if ( ! is_array( $raw ) || ! isset( $raw['schema_version'] ) || ! is_int( $raw['schema_version'] ) ) { return $this->result( 'malformed_storage', null, array( 'malformed_storage' ) ); }
			if ( Schema::VERSION === $raw['schema_version'] ) {
				$valid = ( new Schema() )->validate( $raw );
				return $valid['errors'] ? $this->result( 'malformed_storage', null, $valid['errors'] ) : $this->result( 'ready', $raw );
			}
			$plan = $this->migrations->prepare( $raw );
			return $plan['errors'] ? $this->result( $plan['errors'][0], null, $plan['errors'] ) : $this->result( 'upgrade_required', $raw );
		} catch ( Storage_Exception $error ) { return $this->result( $error->getMessage(), null, array( $error->getMessage() ), $error->outcome() ); }
	}
	public function initialize() {
		$status = $this->status();
		if ( 'absent' !== $status['state'] ) { return $status; }
		return $this->commit( null, Schema::initial(), 'initialized' );
	}
	public function upgrade() {
		$status = $this->status();
		if ( 'upgrade_required' !== $status['state'] ) { return $status; }
		$before = $status['data'];
		$plan = $this->migrations->prepare( $before );
		if ( $plan['errors'] ) { return $this->result( 'migration_failed', null, $plan['errors'] ); }
		return $this->commit( $before['revision'], $plan['data'], 'upgraded' );
	}
	private function commit( $revision, array $next, $success ) {
		try {
			if ( $this->store->compare_and_swap( $revision, $next ) ) { return $this->result( $success, $next, array(), 'committed' ); }
			// A competing initializer/upgrader may already have succeeded. Read only.
			$current = $this->status();
			return 'ready' === $current['state'] ? $current : $this->result( 'storage_conflict', null, array( 'storage_conflict' ) );
		} catch ( Storage_Exception $error ) { return $this->result( $error->getMessage(), null, array( $error->getMessage() ), $error->outcome() ); }
	}
	private function result( $state, $data, array $errors = array(), $outcome = 'not_committed' ) {
		return array( 'state' => $state, 'data' => $data, 'errors' => $errors, 'outcome' => $outcome );
	}
}
