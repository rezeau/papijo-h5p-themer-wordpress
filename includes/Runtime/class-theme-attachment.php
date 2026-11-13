<?php
/** Public parent-page asset only; no viewer or preference reads. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;
use Papijo\H5PThemerWordPress\Compatibility\Runtime_Policy;

final class Theme_Attachment {

	const HANDLE = 'pjh5pwp-theme-controller';
	const ASSET_VERSION = '0.2.0-beta.1-viewer-1';
	private $playback;
	private $enabled;
	private $admin_grant;
	public function __construct( Playback_Registry $playback, ?callable $enabled = null ) {
		$this->playback = $playback;
		$this->enabled = $enabled ?: array( Runtime_Policy::class, 'configured' );
	}
	private function enabled() {
		try { return true === call_user_func( $this->enabled ); }
		catch ( \Throwable $error ) { return false; }
	}
	public function register() {
		// Configuration opt-out registers no attachment hooks; admission is checked at use.
		if ( ! $this->enabled() ) { return; }
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 5 );
		add_action( 'wp_footer', array( $this, 'footer' ), 19 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 5 );
		add_action( 'admin_footer', array( $this, 'admin_footer' ), 19 );
		add_filter( 'print_h5p_content', array( $this, 'mark' ), PHP_INT_MAX, 2 );
		add_filter( 'script_loader_tag', array( $this, 'tag' ), PHP_INT_MAX, 2 );
	}
	public function manifest() {
		if ( ! $this->enabled() ) { return null; }
		$players = $this->playback->candidates(); // Fresh shared policy AND frontend context.
		if ( ! $players || count( $players ) > 64 ) { return null; }
		$endpoint = admin_url( 'admin-ajax.php' );
		$origin = WordPress_Context::origin( $endpoint );
		if ( null === $origin || $origin !== WordPress_Context::origin( home_url( '/' ) ) ) { return null; }
		$manifest = array( 'version' => 1, 'endpoint' => $endpoint, 'players' => $players );
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			$grant = $this->admin_grant();
			if ( null === $grant || ! in_array( $grant['content_id'], array_column( $players, 'content_id' ), true ) ) { return null; }
			$manifest['admin_viewer'] = $grant;
		}
		return $manifest;
	}
	private function admin_grant() {
		$id = WordPress_Context::admin_viewer_id();
		if ( null === $id || ! function_exists( 'wp_create_nonce' ) ) { return null; }
		if ( ! is_array( $this->admin_grant ) || $this->admin_grant['content_id'] !== $id ) {
			$this->admin_grant = array( 'content_id' => $id, 'nonce' => wp_create_nonce( WordPress_Context::admin_nonce_action( $id ) ) );
		}
		return $this->admin_grant;
	}
	public function admin_enqueue() { if ( null !== WordPress_Context::admin_viewer_id() ) { $this->enqueue(); } }
	public function admin_footer() { if ( null !== WordPress_Context::admin_viewer_id() ) { $this->footer(); } }
	public function enqueue() {
		if ( ! $this->eligible() ) { return; }
		wp_enqueue_style( 'pjh5pwp-runtime-readiness', plugins_url( 'assets/css/runtime-readiness.css', PJH5PWP_FILE ), array(), self::ASSET_VERSION );
		wp_enqueue_script( self::HANDLE, plugins_url( 'assets/js/theme-controller.js', PJH5PWP_FILE ), array(), self::ASSET_VERSION, false );
	}
	private function eligible() {
		return $this->enabled() && $this->playback->attachment_permitted() &&
			WordPress_Context::origin( admin_url('admin-ajax.php') ) !== null &&
			WordPress_Context::origin( admin_url('admin-ajax.php') ) === WordPress_Context::origin( home_url('/') );
	}
	public function mark( $html, $content ) {
		$manifest = $this->manifest();
		if ( null === $manifest || ! is_array($content) ) { return $html; }
		foreach ( $manifest['players'] as $p ) {
			if ( (string) ($content['id'] ?? '') !== $p['content_id'] ) { continue; }
			if ( ! $this->playback->matches_markup($p['content_id'], $html) ) { return $html; }
			$id = esc_attr($p['content_id']);
			$prefix = 'div' === $p['embed'] ? '<div class="h5p-content" data-content-id="'.$id.'"' : '<div class="h5p-iframe-wrapper"';
			if ( 0 !== strpos($html, $prefix . '>') ) { return $html; }
			return '<span hidden class="pjh5pwp-readiness-status" data-pjh5pwp-status="'.$id.'" data-pjh5pwp-fallback-text="'.esc_attr__('Activity appearance unavailable; showing original styling.', 'papijo-h5p-themer-wordpress').'" role="status">'.esc_html__('Loading activity appearance…', 'papijo-h5p-themer-wordpress').'</span>'.
				substr_replace($html, ' data-pjh5pwp-pending="'.$id.'"', strlen($prefix), 0);
		}
		return $html;
	}
	public function footer() {
		$manifest = $this->manifest();
		if ( null !== $manifest ) { echo '<span hidden data-pjh5pwp-manifest="'.esc_attr(wp_json_encode($manifest)).'"></span>'; }
	}
	public function tag( $tag, $handle ) {
		if ( self::HANDLE !== $handle ) { return $tag; }
		if ( ! $this->eligible() || 1 !== substr_count($tag, '<script ') ||
			preg_match('/\s(?:async|defer)(?:\s|=|>)/i', $tag) || preg_match('/\stype\s*=\s*[\x22\x27]?module/i', $tag) || strpos($tag, 'data-pjh5pwp-') !== false ) { return ''; }
		$attributes = ' data-pjh5pwp-bootstrap="1"';
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			$grant = $this->admin_grant();
			if ( null === $grant ) { return ''; }
			$attributes .= ' data-pjh5pwp-admin-viewer="' . esc_attr( $grant['content_id'] ) .
				'" data-pjh5pwp-admin-nonce="' . esc_attr( $grant['nonce'] ) . '"';
		}
		return str_replace('<script ', '<script' . $attributes . ' ', $tag);
	}
}
