<?php
/** Native Backdrop source validation/projection only. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

final class Backdrop_Adapter {
	public function project( \stdClass $object ) {
		$data = get_object_vars( $object );
		$schema = $data['schema_version'] ?? null;
		$required = array( '_config_name', 'schema_version', 'id', 'label', 'density', 'colors' );
		if ( 2 === $schema ) { $required[] = 'base_preset'; }
		if ( ! in_array( $schema, array( 1, 2 ), true ) || array_diff( $required, array_keys( $data ) ) ||
			array_diff( array_keys( $data ), array_merge( $required, array( 'description', '_config_static' ) ) ) ||
			! is_string( $data['id'] ?? null ) || ( $data['_config_name'] ?? null ) !== 'h5p_themer.theme.' . $data['id'] ||
			! in_array( $data['density'] ?? null, array( 'small', 'medium', 'large' ), true ) ||
			( array_key_exists( '_config_static', $data ) && ! is_bool( $data['_config_static'] ) ) ) {
			return array( 'data' => null, 'errors' => array( 'invalid_backdrop_record' ) );
		}
		$record = new \stdClass();
		foreach ( array( 'id', 'label', 'description' ) as $key ) {
			if ( array_key_exists( $key, $data ) ) { $record->$key = $data[$key]; }
		}
		$record->base_preset = 2 === $schema ? $data['base_preset'] : null;
		$record->colors = $data['colors'];
		if ( array() === $record->colors ) { $record->colors = new \stdClass(); }
		// Only validated source metadata is discarded. No placeholder is invented.
		return ( new Theme_JSON() )->record( $record );
	}
}
