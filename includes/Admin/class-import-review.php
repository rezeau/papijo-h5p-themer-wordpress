<?php
/** Stateless, expiring, user/session-bound review authentication. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Admin;
use Papijo\H5PThemerWordPress\Transfer\JSON_Decoder;

final class Import_Review {
	const TTL = 900;
	private $secret; private $owner; private $clock;
	public function __construct( ?callable $secret = null, ?callable $owner = null, ?callable $clock = null ) {
		$this->secret = $secret ?: static function () { return wp_salt( 'nonce' ); };
		$this->owner = $owner ?: static function () {
			$id = get_current_user_id(); $session = wp_get_session_token();
			if ( $id <= 0 || ! is_string( $session ) || '' === $session ) { throw new \RuntimeException(); }
			return $id . ':' . $session;
		};
		$this->clock = $clock ?: static function () { return time(); };
	}
	private function signature( array $fields ) {
		$key = call_user_func( $this->secret ); $owner = call_user_func( $this->owner );
		if ( ! is_string( $key ) || strlen( $key ) < 32 || ! is_string( $owner ) || '' === $owner ) { throw new \RuntimeException(); }
		return hash_hmac( 'sha256', serialize( array( 'pjh5pwp-import-review-v1', $owner, $fields ) ), $key );
	}
	public function issue( $json, array $plan ) {
		try {
			$fields = array( 'revision' => (string) $plan['expected_revision'], 'context' => $plan['snapshot_context'], 'digest' => hash( 'sha256', $json ), 'expires' => (string) ( call_user_func( $this->clock ) + self::TTL ) );
			return array( 'data' => $fields + array( 'payload' => base64_encode( $json ), 'signature' => $this->signature( $fields ) ), 'errors' => array() );
		} catch ( \Throwable $error ) { return $this->error( 'review_unavailable' ); }
	}
	public function verify( $review ) {
		if ( ! is_array( $review ) || count( $review ) !== 6 || array_diff( array( 'revision', 'context', 'digest', 'expires', 'payload', 'signature' ), array_keys( $review ) ) ) { return $this->error( 'invalid_review' ); }
		foreach ( $review as $value ) { if ( ! is_string( $value ) ) { return $this->error( 'invalid_review' ); } }
		if ( strlen( $review['payload'] ) > 4 * (int) ceil( JSON_Decoder::MAX_BYTES / 3 ) ||
			! preg_match( '/\A(?:0|[1-9][0-9]{0,18})\z/', $review['revision'] ) ||
			! preg_match( '/\A[1-9][0-9]{0,10}\z/', $review['expires'] ) ) { return $this->error( 'invalid_review' ); }
		foreach ( array( 'context', 'digest', 'signature' ) as $key ) { if ( ! preg_match( '/\A[a-f0-9]{64}\z/', $review[$key] ) ) { return $this->error( 'invalid_review' ); } }
		try {
			$fields = array_intersect_key( $review, array_flip( array( 'revision', 'context', 'digest', 'expires' ) ) );
			// Fixed order, independent of HTTP field ordering.
			$fields = array( 'revision' => $fields['revision'], 'context' => $fields['context'], 'digest' => $fields['digest'], 'expires' => $fields['expires'] );
			if ( ! hash_equals( $this->signature( $fields ), $review['signature'] ) ) { return $this->error( 'invalid_review' ); }
			$now = call_user_func( $this->clock ); $expires = (int) $review['expires'];
			if ( $now > $expires || $expires > $now + self::TTL ) { return $this->error( 'review_expired' ); }
			$json = base64_decode( $review['payload'], true );
			if ( false === $json || strlen( $json ) > JSON_Decoder::MAX_BYTES || base64_encode( $json ) !== $review['payload'] || ! hash_equals( $review['digest'], hash( 'sha256', $json ) ) ) { return $this->error( 'invalid_review' ); }
			return array( 'data' => array( 'json' => $json, 'revision' => $review['revision'], 'context' => $review['context'], 'digest' => $review['digest'] ), 'errors' => array() );
		} catch ( \Throwable $error ) { return $this->error( 'invalid_review' ); }
	}
	private function error( $code ) { return array( 'data' => null, 'errors' => array( $code ) ); }
}
