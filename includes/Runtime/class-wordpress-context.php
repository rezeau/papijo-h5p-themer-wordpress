<?php
/** Conservative CMS/page and same-origin request qualification. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;

final class WordPress_Context {

	/** Existing frontend admission stays independent of the opt-in admin viewer. */
	public static function playback() { return self::frontend() || null !== self::admin_viewer_id(); }

	/** Strict official route parser, shared by the page and authenticated transport. */
	public static function admin_route_id( $url, $admin ) {
		if ( ! is_string( $url ) || preg_match( '/[\\x00-\\x20\\x7f\\\\\\\\]/', $url ) ) { return null; }
		$p = parse_url( $url );
		$path = rtrim( parse_url( $admin, PHP_URL_PATH ) ?: '/', '/' ) . '/admin.php';
		if ( ! is_array( $p ) || ( $p['path'] ?? '' ) !== $path || isset( $p['fragment'] ) ||
			( isset( $p['host'] ) && self::origin( $url ) !== self::origin( $admin ) ) ) { return null; }
		$parts = explode( '&', $p['query'] ?? '' ); $query = array();
		if ( count( $parts ) !== 3 ) { return null; }
		foreach ( $parts as $part ) {
			if ( ! preg_match( '/\\A(?:page=h5p|task=show|id=[1-9][0-9]{0,9})\\z/', $part ) ) { return null; }
			list( $key, $value ) = explode( '=', $part, 2 );
			if ( isset( $query[$key] ) ) { return null; } $query[$key] = $value;
		}
		return count( $query ) === 3 ? ( $query['id'] ?? null ) : null;
	}

	/** Never instantiate H5P services or load/filter activity parameters here. */
	private static function official_admin() {
		try {
			if ( ! defined( 'WP_PLUGIN_DIR' ) || ! class_exists( 'H5PCore' ) ||
				\H5PCore::$coreApi !== array( 'majorVersion' => 1, 'minorVersion' => 28 ) ) { return false; }
			foreach ( array( 'H5P_Plugin' => 'public/class-h5p-plugin.php',
				'H5P_Plugin_Admin' => 'admin/class-h5p-plugin-admin.php',
				'H5PContentAdmin' => 'admin/class-h5p-content-admin.php' ) as $class => $relative ) {
				if ( ! class_exists( $class ) ) { return false; }
				$expected = realpath( WP_PLUGIN_DIR . '/h5p/' . $relative );
				if ( ! $expected || realpath( ( new \ReflectionClass( $class ) )->getFileName() ) !== $expected ) { return false; }
			}
			return true;
		} catch ( \Throwable $error ) { return false; }
	}

	/** Mirror official H5P viewing capabilities/ownership using a read-only identity query. */
	public static function admin_content_allowed( $id ) {
		try {
			if ( ! is_string( $id ) || ! preg_match( '/\\A[1-9][0-9]{0,9}\\z/', $id ) ||
				! self::official_admin() || ! function_exists( 'get_current_user_id' ) ||
				get_current_user_id() <= 0 || ! current_user_can( 'view_h5p_contents' ) ) { return false; }
			global $wpdb;
			if ( ! is_object( $wpdb ) || ! is_string( $wpdb->prefix ?? null ) ||
				! preg_match( '/\\A[A-Za-z0-9_]+\\z/', $wpdb->prefix ) ) { return false; }
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT id, user_id FROM ' . $wpdb->prefix . 'h5p_contents WHERE id = %d LIMIT 1', $id ) );
			return empty( $wpdb->last_error ) && is_object( $row ) && (string) ( $row->id ?? '' ) === $id &&
				( current_user_can( 'view_others_h5p_contents' ) || (string) ( $row->user_id ?? '' ) === (string) get_current_user_id() );
		} catch ( \Throwable $error ) { return false; }
	}

	public static function admin_viewer_id() {
		try {
			if ( ! function_exists( 'is_admin' ) || ! is_admin() || wp_doing_ajax() ||
				( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ||
				( defined( 'WP_CLI' ) && WP_CLI ) || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ||
				! in_array( $_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'document', array( 'document', '' ), true ) ||
				( $GLOBALS['pagenow'] ?? null ) !== 'admin.php' || ( $GLOBALS['plugin_page'] ?? null ) !== 'h5p' ||
				! function_exists( 'get_current_screen' ) ) { return null; }
			$screen = get_current_screen();
			if ( ! is_object( $screen ) || ( $screen->id ?? null ) !== 'toplevel_page_h5p' ) { return null; }
			$id = self::admin_route_id( $_SERVER['REQUEST_URI'] ?? '', admin_url() );
			if ( null === $id || count( $_GET ) !== 3 || ( $_GET['page'] ?? null ) !== 'h5p' ||
				( $_GET['task'] ?? null ) !== 'show' || ( $_GET['id'] ?? null ) !== $id ) { return null; }
			return self::admin_content_allowed( $id ) ? $id : null;
		} catch ( \Throwable $error ) { return null; }
	}

	public static function player_allowed( array $content ) {
		return ! is_admin() || self::admin_content_allowed( (string) ( $content['id'] ?? '' ) );
	}

	public static function admin_nonce_action( $id ) { return 'pjh5pwp_admin_viewer_' . $id; }

	/** Fresh session/capability/ownership checks for every player, never a referrer-only grant. */
	public static function admin_transport_allowed( array $server, array $post, $admin ) {
		try {
			$id = self::admin_route_id( $server['HTTP_REFERER'] ?? '', $admin );
			if ( null === $id || count( $post ) !== 5 || ( $post['admin_content_id'] ?? null ) !== $id ||
				! is_string( $post['admin_nonce'] ?? null ) || ! preg_match( '/\\A[a-f0-9]{10}\\z/', $post['admin_nonce'] ) ||
				! self::admin_content_allowed( $id ) || ! wp_verify_nonce( $post['admin_nonce'], self::admin_nonce_action( $id ) ) ||
				! is_string( $post['admin_players'] ?? null ) || strlen( $post['admin_players'] ) > 703 ) { return false; }
			$ids = explode( ',', $post['admin_players'] );
			if ( count( $ids ) > 64 || count( $ids ) !== count( array_unique( $ids ) ) || ! in_array( $id, $ids, true ) ) { return false; }
			foreach ( $ids as $player ) { if ( ! self::admin_content_allowed( $player ) ) { return false; } }
			return true;
		} catch ( \Throwable $error ) { return false; }
	}

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
		return self::same_origin_request( $server, $home, $admin ) && self::frontend_referrer( $server, $home );
	}

	/** Common transport checks are identical for frontend and the narrower admin branch. */
	public static function same_origin_request( array $server, $home, $admin ) {
		$origin = self::origin( $home );
		if ( null === $origin || $origin !== self::origin( $admin ) ||
			$origin !== self::origin( $server['HTTP_ORIGIN'] ?? null, true ) ||
			$origin !== self::origin( $server['HTTP_REFERER'] ?? null ) ||
			'appearance-v1' !== ( $server['HTTP_X_PJH5PWP_REQUEST'] ?? null ) ) { return false; }
		if ( isset( $server['HTTP_SEC_FETCH_SITE'] ) && 'same-origin' !== $server['HTTP_SEC_FETCH_SITE'] ) { return false; }
		if ( isset( $server['HTTP_SEC_FETCH_DEST'] ) && 'empty' !== $server['HTTP_SEC_FETCH_DEST'] ) { return false; }
		if ( isset( $server['HTTP_SEC_FETCH_MODE'] ) && ! in_array( $server['HTTP_SEC_FETCH_MODE'], array( 'cors', 'same-origin' ), true ) ) { return false; }
		return true;
	}
	private static function frontend_referrer( array $server, $home ) {
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
