<?php
/**
 * Palette resolution adapted from Backdrop v0.5.0; no storage or density profile.
 * Original grants: GPL-2.0-or-later. This adaptation: GPL-3.0-or-later.
 * @package Papijo\H5PThemerWordPress
 */
namespace Papijo\H5PThemerWordPress\Theme;

final class Palette_Resolver {
	/** Failure is explicit. Fallback belongs to selection policy, not resolution. */
	public function resolve( $selection, $definitions = array(), $schema = 2 ) {
		$validator = new Theme_Validator();
		$reference = $validator->reference( $selection, $schema );
		if ( $reference['errors'] ) {
			return $reference;
		}
		$identity = $reference['data'];
		if ( 'core' === $identity['kind'] ) {
			return array( 'data' => array( 'kind' => 'core', 'id' => null, 'label' => 'H5P Core defaults', 'colors' => array(), 'unsupported_properties' => array() ), 'errors' => array() );
		}
		if ( 'builtin' === $identity['kind'] ) {
			$preset = ( new Preset_Catalog() )->get( $identity['id'], $identity['version'] );
			$preset['data'] = array( 'kind' => 'builtin' ) + $preset['data'];
			unset( $preset['data']['density'] );
			return $preset;
		}
		$id = $identity['id'];
		if ( ! is_array( $definitions ) || ! array_key_exists( $id, $definitions ) ) {
			return array( 'data' => null, 'errors' => array( 'missing_custom_theme' ) );
		}
		$definition = $validator->definition( $id, $definitions[ $id ] );
		if ( $definition['errors'] ) {
			return $definition;
		}
		$data        = $definition['data'];
		$colors      = $data['colors'];
		$base        = null;
		$unsupported = array();
		if ( 2 === $data['schema_version'] ) {
			$base        = $data['base_preset'];
			$preset      = ( new Preset_Catalog() )->get( $base['id'], $base['version'] )['data'];
			$colors      = array_replace( $preset['colors'], $colors );
			$unsupported = $preset['unsupported_properties'];
		}
		return array( 'data' => array( 'kind' => 'custom', 'id' => $id, 'label' => $data['label'], 'colors' => $colors,
			'base_preset' => $base, 'unsupported_properties' => $unsupported ), 'errors' => array() );
	}
}
