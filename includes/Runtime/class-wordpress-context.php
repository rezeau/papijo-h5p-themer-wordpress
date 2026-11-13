<?php
/** Conservative CMS/page and same-origin request qualification. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;

final class WordPress_Context {

	public static function frontend() {
		return ! is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) &&
			! ( defined( 'DOING_CRON' ) && DOING_CRON ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) &&
			did_action( 'wp' ) > 0 && ! is_preview() && ! is_customize_preview() && ! is_embed() && ! is_feed() &&
			'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) &&
			in_array( $_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'document', array( 'document', '' ), true );
	}

	/** Origin syntax is intentionally strict: a serialized HTTP(S) origin, no path. */
	public static function origin( $url, $origin_header = false ) {
		if ( ! is_string( $url ) || preg_match( '/[\x00-\x20\x7f\\\\]/', $url ) ) { return null; }
		$p = parse_url( $url );
		if ( ! is_array( $p ) || ! isset( $p['scheme'], $p['host'] ) || isset( $p['user'] ) || isset( $p['pass'] ) ||
			! in_array( strtolower( $p['scheme'] ), array( 'http', 'https' ), true ) ) { return null; }
		if ( $origin_header && ( isset( $p['path'] ) || isset( $p['query'] ) || isset( $p['fragment'] ) ) ) { return null; }
		$scheme = strtolower( $p['scheme'] );
		return $scheme . '://' . strtolower( $p['host'] ) . ':' . ( $p['port'] ?? ( 'https' === $scheme ? 443 : 80 ) );
	}

	/** Only a same-origin frontend parent may request transport. This is not DOM ownership proof. */
	public static function request_allowed( array $server, $home, $admin ) {
		$origin = self::origin( $home );
		if ( null === $origin || $origin !== self::origin( $admin ) ||
			$origin !== self::origin( $server['HTTP_ORIGIN'] ?? null, true ) ||
			$origin !== self::origin( $server['HTTP_REFERER'] ?? null ) ||
			'appearance-v1' !== ( $server['HTTP_X_PJH5PWP_REQUEST'] ?? null ) ) { return false; }
		if ( isset( $server['HTTP_SEC_FETCH_SITE'] ) && 'same-origin' !== $server['HTTP_SEC_FETCH_SITE'] ) { return false; }
		if ( isset( $server['HTTP_SEC_FETCH_DEST'] ) && 'empty' !== $server['HTTP_SEC_FETCH_DEST'] ) { return false; }
		if ( isset( $server['HTTP_SEC_FETCH_MODE'] ) && ! in_array( $server['HTTP_SEC_FETCH_MODE'], array( 'cors', 'same-origin' ), true ) ) { return false; }
		$p = parse_url( $server['HTTP_REFERER'] );
		$path = rawurldecode( $p['path'] ?? '/' );
		$home_path = rtrim( rawurldecode( parse_url( $home, PHP_URL_PATH ) ?: '/' ), '/' ) . '/';
		if ( 0 !== strpos( $path, $home_path ) || preg_match( '~(?:^|/)(?:wp-admin|wp-json|wp-login\.php|embed|\.\.?)(?:/|$)|[\\\\\x00-\x20\x7f%]~i', $path ) ) { return false; }
		parse_str( $p['query'] ?? '', $query );
		foreach ( array( 'preview', 'preview_id', 'preview_nonce', 'customize_changeset_uuid', 'customize_theme', 'elementor-preview', 'rest_route', 'action', 'embed' ) as $key ) {
			if ( array_key_exists( $key, $query ) ) { return false; }
		}
		return true;
	}
}
