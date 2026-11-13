<?php
/** Request-local, non-personal playback candidates. No HTML/asset output. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;

final class Playback_Registry {

	private $admission;
	private $frontend;
	private $content_allowed;
	private $candidates = array();
	private $seen = array();
	private $markup = array();
	public function __construct( Runtime_Admission $admission, callable $frontend, ?callable $content_allowed = null ) {
		$this->admission = $admission;
		$this->frontend = $frontend;
		$this->content_allowed = $content_allowed ?: static function () { return true; };
	}
	public function register() { add_filter( 'print_h5p_content', array( $this, 'observe' ), PHP_INT_MAX, 2 ); }
	/** Public assets may precede discovery; reuse the same policy and context. */
	public function attachment_permitted() { return $this->permitted(); }
	public function matches_markup( $id, $html ) { return isset($this->candidates[$id], $this->markup[$id]) && $this->markup[$id] === $html; }

	/** Filter always returns the original bytes. Never infer main identity from dependencies. */
	public function observe( $html, $content ) {
		if ( ! $this->permitted() || ! is_array( $content ) ) { return $html; }
		$id = $content['id'] ?? null;
		if ( ! ( is_int( $id ) || is_string( $id ) ) || ! preg_match( '/\A[1-9][0-9]{0,9}\z/', (string) $id ) ) { return $html; }
		$id = (string) $id;
		try { if ( true !== call_user_func( $this->content_allowed, $content ) ) { return $html; } }
		catch ( \Throwable $error ) { return $html; }
		// Repeated IDs cannot be mapped safely to H5P's shared cid-N settings/iframe IDs.
		if ( isset( $this->seen[ $id ] ) ) { unset( $this->candidates[ $id ] ); return $html; }
		$this->seen[ $id ] = true;
		$library = $content['library'] ?? null;
		if ( ! is_array( $library ) || ! is_string( $library['name'] ?? null ) ||
			! preg_match( '/\AH5P\.[A-Za-z][A-Za-z0-9]*\z/', $library['name'] ) ) { return $html; }
		if ( ! ( is_int( $library['id'] ?? null ) || is_string( $library['id'] ?? null ) ) ||
			! preg_match( '/\A[1-9][0-9]{0,9}\z/', (string) $library['id'] ) ) { return $html; }
		foreach ( array( 'majorVersion', 'minorVersion' ) as $key ) {
			if ( ! ( is_int( $library[ $key ] ?? null ) || is_string( $library[ $key ] ?? null ) ) ||
				! preg_match( '/\A[0-9]{1,4}\z/', (string) $library[ $key ] ) ) { return $html; }
		}
		if ( ! in_array( $content['embedType'] ?? null, array( 'div', 'iframe' ), true ) ||
			! is_string( $library['embedTypes'] ?? null ) ||
			( ! preg_match( '/\A(?:div|iframe)(?:, (?:div|iframe))?\z/', $library['embedTypes'] ) &&
				! ( '' === $library['embedTypes'] && 'div' === $content['embedType'] ) ) ) { return $html; }
		// Verified H5PCore method, reached only after the shared capability gate.
		try { $embed = \H5PCore::determineEmbedType( $content['embedType'], $library['embedTypes'] ); }
		catch ( \Throwable $error ) { return $html; }
		$expected = '<div class="h5p-content" data-content-id="' . $id . '"></div>';
		if ( 'iframe' === $embed ) {
			if ( ! is_array( $content['metadata'] ?? null ) ) { return $html; }
			$title = $content['metadata']['a11yTitle'] ?? ( $content['metadata']['title'] ?? '' );
			if ( ! is_string( $title ) ) { return $html; }
			$expected = '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $id . '" class="h5p-iframe" data-content-id="' . $id . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no" title="' . esc_attr( $title ) . '"></iframe></div>';
		}
		if ( ! in_array( $embed, array( 'div', 'iframe' ), true ) || $expected !== $html ) { return $html; }
		$this->candidates[ $id ] = array( 'content_id' => $id, 'embed' => $embed,
			'library' => $library['name'] . ' ' . $library['majorVersion'] . '.' . $library['minorVersion'] );
		$this->markup[$id] = $html;
		return $html;
	}

	/** Dormant attachment seam. Recheck policy even if a conflict activates after observe(). */
	public function candidates() {
		if ( ! $this->permitted() ) { return array(); }
		$items = $this->candidates;
		foreach ( $items as $id => $item ) {
			try { if ( true !== call_user_func( $this->content_allowed, array( 'id' => (string) $id ) ) ) { unset( $items[$id] ); } }
			catch ( \Throwable $error ) { unset( $items[$id] ); }
		}
		ksort( $items, SORT_STRING );
		return array_values( $items );
	}
	private function permitted() {
		try {
			$p = $this->admission->evaluate( call_user_func( $this->frontend ) );
			return is_array( $p ) && true === ( $p['future_runtime_permitted'] ?? null ) &&
				false === ( $p['runtime_suspended'] ?? null ) && array() === ( $p['blockers'] ?? null );
		} catch ( \Throwable $error ) { return false; }
	}
}
