<?php
/** Pure appearance projection; no HTTP, hooks, assets or persistence. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;

/** Dependencies supply Runtime_Policy::evaluate() and Profile_Service::effective(). */
final class Appearance_Service {

	private $admission;
	private $effective;
	private $serializer;

	public function __construct( callable $admission, callable $effective, ?CSS_Serializer $serializer = null ) {
		$this->admission = $admission;
		$this->effective = $effective;
		$this->serializer = $serializer ?: new CSS_Serializer();
	}

	/** Viewer identity is server-supplied; zero means anonymous. Never returned. */
	public function describe( $viewer_id ) {
		try { $policy = call_user_func( $this->admission ); }
		catch ( \Throwable $error ) { return $this->suspended( 'admission_unavailable' ); }
		if ( ! is_array( $policy ) || true !== ( $policy['future_runtime_permitted'] ?? null ) ||
			false !== ( $policy['runtime_suspended'] ?? null ) || array() !== ( $policy['blockers'] ?? null ) ) {
			$snordian = is_array( $policy ) && is_array( $policy['blockers'] ?? null ) && in_array( 'snordian_active', $policy['blockers'], true );
			return $this->suspended( $snordian ? 'snordian_active' : 'runtime_policy' );
		}
		if ( ! is_int( $viewer_id ) || $viewer_id < 0 ) { return $this->suspended( 'appearance_unavailable' ); }
		try { $effective = call_user_func( $this->effective, $viewer_id ); }
		catch ( \Throwable $error ) { return $this->suspended( 'appearance_unavailable' ); }
		$resolved = is_array( $effective ) ? ( $effective['theme'] ?? null ) : null;
		if ( ! is_array( $resolved ) || array() !== ( $resolved['errors'] ?? null ) ||
			! in_array( $resolved['source'] ?? null, array( 'personal', 'global', 'core' ), true ) ) {
			return $this->suspended( 'appearance_unavailable' );
		}
		$theme = $resolved['data'] ?? null;
		$css = $this->serializer->serialize( $theme );
		if ( $css['errors'] ) { return $this->suspended( 'appearance_unavailable' ); }
		$density = $effective['density'] ?? 'original';
		if ( ! in_array( $density, array( 'original', 'small', 'medium', 'large' ), true ) ) { $density = 'original'; }
		return array(
			'admission' => array( 'permitted' => true, 'reason' => null ),
			'theme' => array( 'source' => $resolved['source'], 'kind' => $theme['kind'],
				'id' => 'core' === $theme['kind'] ? null : $theme['id'],
				'version' => 'builtin' === $theme['kind'] ? $theme['version'] : null ),
			'color_declarations' => $css['data'],
			'density' => array( 'selection' => $density, 'preserve_native' => 'original' === $density ),
		);
	}

	private function suspended( $reason ) {
		return array( 'admission' => array( 'permitted' => false, 'reason' => $reason ),
			'theme' => null, 'color_declarations' => '', 'density' => null );
	}
}
