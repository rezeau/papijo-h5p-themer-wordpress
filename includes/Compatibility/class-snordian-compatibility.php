<?php
/**
 * Compatibility identifiers only; no SNORDIAN implementation is reused.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress\Compatibility;

defined( 'ABSPATH' ) || exit;

/** Detect both canonical activation and loaded markers from a renamed copy. */
final class Snordian_Compatibility {

	const BASENAME = 'snordians-h5p-themer/snordians-h5p-themer.php';
	const CALLBACK = 'Snordian\\H5PThemer\\h5p_themer_alter_scripts';

	private $environment;

	public function __construct( WordPress_Environment $environment ) {
		$this->environment = $environment;
	}

	public function inspect() {
		$signals = array();
		if ( $this->environment->active( self::BASENAME ) ) {
			$signals[] = 'active_plugin';
		}
		if ( defined( 'SNORDIANSH5PTHEMER_VERSION' ) ) {
			$signals[] = 'version_constant';
		}
		if ( function_exists( self::CALLBACK ) || class_exists( 'Snordian\\H5PThemer\\Main', false ) ) {
			$signals[] = 'loaded_namespace';
		}
		// Priority zero is a valid callback priority, so test strictly against false.
		if ( false !== has_action( 'h5p_alter_library_scripts', self::CALLBACK ) ) {
			$signals[] = 'runtime_callback';
		}
		return array(
			'installed' => $this->environment->installed( self::BASENAME ),
			'active'    => ! empty( $signals ),
			'version'   => $this->environment->version( self::BASENAME ),
			'signals'   => $signals,
		);
	}
}
