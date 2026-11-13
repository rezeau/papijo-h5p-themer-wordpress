<?php
/** Read-only application boundary; no routes, headers or persistence. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Theme\Theme_Validator;

final class Export_Service {
	private $store;
	private $authorize;
	/** Authorization override is trusted application wiring, never request data. */
	public function __construct( Snapshot_Store $store, ?callable $authorize = null ) {
		$this->store = $store;
		$this->authorize = $authorize ?: static function () {
			// Same management policy as Admin_Service::allowed(), without loading UI code.
			return function_exists( 'current_user_can' ) && function_exists( 'is_multisite' ) &&
				current_user_can( 'manage_options' ) && ! is_multisite();
		};
	}
	private function allowed() {
		try { return true === call_user_func( $this->authorize ); }
		catch ( \Throwable $error ) { return false; }
	}
	/** Selection: {mode:single,id}, {mode:selected,ids:[...]}, or {mode:all}. */
	public function prepare( $selection ) {
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		$valid = $this->selection( $selection );
		if ( $valid['errors'] ) { return $valid; }
		try { $snapshot = $this->store->read(); }
		catch ( \Throwable $error ) { return $this->error( 'export_storage_unavailable' ); }
		// Recheck before disclosing either storage diagnostics or palette data.
		if ( ! $this->allowed() ) { return $this->error( 'forbidden' ); }
		if ( null === $snapshot ) { return $this->error( 'storage_absent' ); }
		try {
			if ( ( new Schema() )->validate( $snapshot )['errors'] ) { return $this->error( 'invalid_theme_library' ); }
			$ids = 'all' === $selection['mode'] ? array_keys( $snapshot['definitions'] ) : $valid['data'];
			if ( ! $ids ) { return $this->error( 'empty_export_selection' ); }
			if ( count( $ids ) > Theme_JSON::MAX_THEMES ) { return $this->error( 'invalid_theme_count' ); }
			$result = ( new Theme_JSON() )->export( $snapshot, $ids );
			if ( $result['errors'] ) { return $result; }
		} catch ( \Throwable $error ) { return $this->error( 'export_unavailable' ); }
		return array( 'data' => array(
			'json' => $result['data'], 'mime_type' => 'application/json', 'encoding' => 'UTF-8',
			'filename' => 'papijo-h5p-theme-colors' . ( 1 === count( $ids ) ? '-' . $ids[0] : '' ) . '.json',
			'disposition' => 'attachment', 'cache_control' => 'private, no-store', 'nosniff' => true,
		), 'errors' => array() );
	}
	private function selection( $selection ) {
		if ( ! is_array( $selection ) || ! isset( $selection['mode'] ) || ! is_string( $selection['mode'] ) ) { return $this->error( 'invalid_export_selection' ); }
		$mode = $selection['mode'];
		if ( 'all' === $mode ) {
			return 1 === count( $selection ) ? array( 'data' => array(), 'errors' => array() ) : $this->error( 'invalid_export_selection' );
		}
		if ( 'single' === $mode && 2 === count( $selection ) && array_key_exists( 'id', $selection ) ) {
			$ids = array( $selection['id'] );
		} elseif ( 'selected' === $mode && 2 === count( $selection ) && isset( $selection['ids'] ) && is_array( $selection['ids'] ) ) {
			$ids = $selection['ids'];
		} else { return $this->error( 'invalid_export_selection' ); }
		if ( ! $ids || count( $ids ) > Theme_JSON::MAX_THEMES || array_keys( $ids ) !== range( 0, count( $ids ) - 1 ) ) { return $this->error( 'invalid_export_selection' ); }
		$seen = array();
		foreach ( $ids as $id ) {
			if ( ! Theme_Validator::valid_id( $id ) ) { return $this->error( 'invalid_export_selection' ); }
			if ( isset( $seen[$id] ) ) { return $this->error( 'duplicate_export_selection' ); }
			$seen[$id] = true;
		}
		return array( 'data' => $ids, 'errors' => array() );
	}
	private function error( $code ) { return array( 'data' => null, 'errors' => array( $code ) ); }
}
