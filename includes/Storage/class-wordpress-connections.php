<?php
/** Factory for private, short-lived WordPress database sessions. */
namespace Papijo\H5PThemerWordPress\Storage;

/** @license GPL-3.0-or-later */
final class WordPress_Connections {
	private $blog;
	public function open() {
		if ( ! function_exists( 'is_multisite' ) || is_multisite() || ! function_exists( 'get_current_blog_id' ) ||
			! isset( $GLOBALS['wpdb'] ) || get_class( $GLOBALS['wpdb'] ) !== 'wpdb' ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
		$blog = get_current_blog_id();
		if ( null !== $this->blog && $this->blog !== $blog ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
		$this->blog = $blog;
		foreach ( array( 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_HOST' ) as $name ) {
			if ( ! defined( $name ) ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
		}
		if ( ! is_string( DB_HOST ) || 0 === stripos( DB_HOST, 'p:' ) ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
		require_once __DIR__ . '/class-owned-wpdb.php';
		try { $db = new Owned_WPDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST ); }
		catch ( \Throwable $error ) { throw new Storage_Exception( 'storage_read_failed' ); }
		$db->suppress_errors( true );
		if ( ! $db->ready ) { $db->close(); throw new Storage_Exception( 'storage_read_failed' ); }
		return array( $db, $GLOBALS['wpdb']->options );
	}
}
