<?php
/** @package Papijo\H5PThemerWordPress @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Theme;

/** Immutable, pinned data. Callers receive arrays by value, never references. */
final class Preset_Catalog {
	const VERSION = 'moodle-478fed30d2a4';

	public function all( $version = self::VERSION ) {
		if ( self::VERSION !== $version ) {
			return array( 'data' => null, 'errors' => array( 'unknown_preset_version' ) );
		}
		static $catalog;
		if ( null === $catalog ) {
			$catalog = require dirname( __DIR__, 2 ) . '/presets/moodle-478fed30d2a4.php';
		}
		return array( 'data' => $catalog, 'errors' => array() );
	}

	public function get( $id, $version ) {
		$catalog = $this->all( $version );
		if ( $catalog['errors'] ) {
			return $catalog;
		}
		if ( ! is_string( $id ) || ! array_key_exists( $id, $catalog['data'] ) ) {
			return array( 'data' => null, 'errors' => array( 'unknown_preset_id' ) );
		}
		return array( 'data' => $catalog['data'][ $id ], 'errors' => array() );
	}
}
