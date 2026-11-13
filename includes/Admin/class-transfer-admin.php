<?php
/** Existing admin-page request boundary, never a standalone public route. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Admin;
use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Transfer\Export_Service;
use Papijo\H5PThemerWordPress\Transfer\Import_Service;
final class Transfer_Admin {
	private $store; private $reviews; private $uploads;
	public function __construct( Snapshot_Store $store, ?Import_Review $reviews = null, ?Transfer_Upload $uploads = null ) {
		$this->store = $store; $this->reviews = $reviews ?: new Import_Review(); $this->uploads = $uploads ?: new Transfer_Upload();
	}
	public static function nonce_action( $op, $source, $revision, $context ) { return Admin_Service::nonce_action( 'transfer:' . $op, $source, $revision, $context ); }
	public function submit( $input, $files ) {
		if ( ! Admin_Service::allowed() ) { return $this->error( 'forbidden' ); }
		if ( ! is_array( $input ) || ! isset( $input['pjh5pwp_op'] ) || ! is_string( $input['pjh5pwp_op'] ) ) { return $this->error( 'malformed_submission' ); }
		$op = $input['pjh5pwp_op'];
		if ( 'import_commit' === $op ) { return $this->commit( $input, $files ); }
		if ( ! in_array( $op, array( 'export', 'import_preview' ), true ) ) { return $this->error( 'malformed_submission' ); }
		foreach ( array( 'revision', 'context', 'nonce' ) as $key ) { if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) || strlen( $input[$key] ) > 128 ) { return $this->error( 'malformed_submission' ); } }
		$keys = array( 'pjh5pwp_op', 'revision', 'context', 'nonce' );
		$source = '';
		if ( 'export' === $op ) {
			if ( $files || ! isset( $input['mode'] ) || ! is_string( $input['mode'] ) ) { return $this->error( 'malformed_submission' ); }
			$source = 'single' === $input['mode'] ? ( $input['id'] ?? null ) : 'bulk';
			if ( ! is_string( $source ) || strlen( $source ) > 64 ) { return $this->error( 'malformed_submission' ); }
			$keys = array_merge( $keys, array( 'mode' ), 'single' === $input['mode'] ? array( 'id' ) : array( 'ids' ) );
		}
		if ( array_diff( array_keys( $input ), $keys ) ) { return $this->error( 'malformed_submission' ); }
		if ( ! wp_verify_nonce( $input['nonce'], self::nonce_action( $op, $source, $input['revision'], $input['context'] ) ) ) { return $this->error( 'invalid_nonce' ); }
		try { $snapshot = $this->store->read(); }
		catch ( \Throwable $error ) { return $this->error( 'storage_read_failed' ); }
		if ( ! Admin_Service::allowed() ) { return $this->error( 'forbidden' ); }
		if ( ! is_array( $snapshot ) || (string) ( $snapshot['revision'] ?? '' ) !== $input['revision'] || ! hash_equals( Admin_Service::context( $snapshot ), $input['context'] ) ) { return $this->error( 'storage_conflict' ); }
		if ( 'export' === $op ) {
			$selection = array( 'mode' => $input['mode'] );
			if ( 'single' === $input['mode'] ) { $selection['id'] = $input['id']; }
			elseif ( 'selected' === $input['mode'] ) { $selection['ids'] = $input['ids'] ?? array(); }
			elseif ( 'all' !== $input['mode'] || isset( $input['ids'] ) ) { return $this->error( 'invalid_export_selection' ); }
			// Deliver the exact authoritative snapshot checked against the displayed form.
			$reader = new class( $snapshot ) implements Snapshot_Store {
				private $snapshot;
				public function __construct( array $snapshot ) { $this->snapshot = $snapshot; }
				public function read() { return $this->snapshot; }
				public function compare_and_swap( $revision, array $next ) { throw new \LogicException(); }
			};
			$result = ( new Export_Service( $reader ) )->prepare( $selection );
			return $result['errors'] ? $this->error( $result['errors'] ) : array( 'errors' => array(), 'download' => $result['data'] );
		}
		$upload = $this->uploads->read( $files );
		if ( $upload['errors'] ) { return $this->error( $upload['errors'] ); }
		$service = new Import_Service( $this->store ); $result = $service->prepare( $upload['data'] );
		if ( $result['errors'] ) { return $this->error( $result['errors'] ); }
		$plan = $result['data'];
		if ( (string) $plan['expected_revision'] !== $input['revision'] || $plan['snapshot_context'] !== $input['context'] ) { return $this->error( 'storage_conflict' ); }
		$review = $this->reviews->issue( $upload['data'], $plan );
		return $review['errors'] ? $this->error( $review['errors'] ) : array( 'errors' => array(), 'review' => $review['data'], 'plan' => $plan );
	}
	private function commit( array $input, $files ) {
		if ( $files || count( $input ) !== 4 || array_diff( array_keys( $input ), array( 'pjh5pwp_op', 'nonce', 'confirm', 'review' ) ) || ! is_string( $input['nonce'] ?? null ) || strlen( $input['nonce'] ) > 128 || '1' !== ( $input['confirm'] ?? null ) ) { return $this->error( 'import_confirmation_required' ); }
		$checked = $this->reviews->verify( $input['review'] );
		if ( $checked['errors'] ) { return $this->error( $checked['errors'] ); }
		$review = $input['review'];
		if ( ! wp_verify_nonce( $input['nonce'], self::nonce_action( 'import_commit', $review['signature'], $review['revision'], $review['context'] ) ) ) { return $this->error( 'invalid_nonce' ); }
		$original = $checked['data'];
		$service = new Import_Service( $this->store ); $prepared = $service->prepare( $original['json'] );
		if ( $prepared['errors'] ) { return $this->error( $prepared['errors'] ); }
		$plan = $prepared['data'];
		if ( (string) $plan['expected_revision'] !== $original['revision'] || $plan['snapshot_context'] !== $original['context'] || $plan['input_digest'] !== $original['digest'] ) { return $this->error( 'storage_conflict' ); }
		// Issue and commit through the SAME Phase 3D instance; never adopt a posted plan.
		$result = $service->commit( $plan );
		return $result['errors'] ? $this->error( $result['errors'], $result['outcome'] ) : array( 'errors' => array(), 'summary' => $result['data'], 'outcome' => $result['outcome'] );
	}
	private function error( $codes, $outcome = 'not_committed' ) { return array( 'errors' => (array) $codes, 'outcome' => $outcome ); }
}
