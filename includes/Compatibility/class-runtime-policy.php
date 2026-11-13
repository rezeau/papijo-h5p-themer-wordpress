<?php
/**
 * Shared runtime configuration and compatibility gate for Theme and Density.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress\Compatibility;

defined( 'ABSPATH' ) || exit;

/** Administrative availability never depends on runtime admission. */
final class Runtime_Policy {

	private $h5p;
	private $snordian;
	/** Configuration only; does not grant compatibility or frontend admission. */
	public static function configured() {
		return ! defined( 'PJH5PWP_ENABLE_BROWSER_THEME' ) || true === constant( 'PJH5PWP_ENABLE_BROWSER_THEME' );
	}

	public function __construct( H5P_Dependency $h5p, Snordian_Compatibility $snordian ) {
		$this->h5p      = $h5p;
		$this->snordian = $snordian;
	}

	/** Fresh decisions prevent a previously permitted result becoming authority. */
	public function evaluate() {
		$h5p      = $this->h5p->inspect();
		$snordian = $this->snordian->inspect();
		$blockers = array();
		if ( is_multisite() ) {
			$blockers[] = 'multisite_deferred';
		}
		if ( ! $h5p['supported'] ) {
			$blockers[] = $h5p['code'];
		}
		if ( $snordian['active'] ) {
			$blockers[] = 'snordian_active';
		}
		return array(
			'h5p'                      => $h5p,
			'snordian'                 => $snordian,
			'blockers'                 => $blockers,
			'runtime_suspended'        => ! empty( $blockers ),
			'future_runtime_permitted' => empty( $blockers ),
			'runtime_implemented'      => true,
			'runtime_enabled'          => self::configured(),
			'runtime_state'            => $blockers ? 'suspended' : ( self::configured() ? 'available' : 'disabled' ),
		);
	}
}
