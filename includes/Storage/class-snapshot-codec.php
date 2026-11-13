<?php
/** Safe native option serialization, without object instantiation. */
namespace Papijo\H5PThemerWordPress\Storage;

/** @license GPL-3.0-or-later */
final class Snapshot_Codec {
	const MAX_BYTES = 4194304;
	public function encode( array $snapshot ) {
		$value = serialize( $snapshot );
		if ( strlen( $value ) > self::MAX_BYTES ) { throw new Storage_Exception( 'storage_too_large' ); }
		return $value;
	}
	public function decode( $bytes ) {
		if ( ! is_string( $bytes ) || strlen( $bytes ) > self::MAX_BYTES ) { throw new Storage_Exception( 'malformed_storage' ); }
		set_error_handler( static function () { throw new Storage_Exception( 'malformed_storage' ); } );
		try {
			$value = unserialize( $bytes, array( 'allowed_classes' => false ) );
		} catch ( \Throwable $error ) {
			throw new Storage_Exception( 'malformed_storage' );
		} finally {
			restore_error_handler();
		}
		// Null is reserved for an absent row; a serialized scalar is malformed data.
		if ( ! is_array( $value ) || serialize( $value ) !== $bytes ) { throw new Storage_Exception( 'malformed_storage' ); }
		return $value;
	}
}
