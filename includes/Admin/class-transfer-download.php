<?php
/** Terminal HTTP delivery for an authorized prepared download only. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Admin;
use Papijo\H5PThemerWordPress\Transfer\JSON_Decoder;
final class Transfer_Download {
	public static function send( array $download, ?callable $emit = null, ?callable $body = null ) {
		if ( ! is_string( $download['json'] ?? null ) || strlen( $download['json'] ) > JSON_Decoder::MAX_BYTES || ! is_string( $download['filename'] ?? null ) || ! preg_match( '/\Apapijo-h5p-theme-colors(?:-[a-z][a-z0-9_]{0,63})?\.json\z/', $download['filename'] ) ||
			'application/json' !== ( $download['mime_type'] ?? null ) || 'UTF-8' !== ( $download['encoding'] ?? null ) || 'attachment' !== ( $download['disposition'] ?? null ) || 'private, no-store' !== ( $download['cache_control'] ?? null ) || true !== ( $download['nosniff'] ?? null ) ) { throw new \RuntimeException( 'invalid_download' ); }
		if ( null === $emit && headers_sent() ) { throw new \RuntimeException( 'download_unavailable' ); }
		$emit = $emit ?: static function ( $header ) { header( $header, true ); };
		$body = $body ?: static function ( $json ) { echo $json; };
		foreach ( array( 'Content-Type: application/json; charset=UTF-8', 'Content-Disposition: attachment; filename="' . $download['filename'] . '"', 'Cache-Control: private, no-store', 'Pragma: no-cache', 'Expires: 0', 'X-Content-Type-Options: nosniff', 'Content-Length: ' . strlen( $download['json'] ) ) as $header ) { call_user_func( $emit, $header ); }
		call_user_func( $body, $download['json'] );
	}
}
