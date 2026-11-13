<?php
/**
 * Declaration validation adapted from Backdrop v0.5.0 h5p_themer.runtime.inc.
 * Original: GPL-2.0-or-later. Adaptation: GPL-3.0-or-later.
 * @package Papijo\H5PThemerWordPress
 */
namespace Papijo\H5PThemerWordPress\Runtime;

use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Validator;

/** Accepts resolved palettes, never CSS text; emits declarations without a selector. */
final class CSS_Serializer {

	public function serialize( $theme ) {
		$invalid = array( 'data' => null, 'errors' => array( 'invalid_runtime_colors' ) );
		if ( ! is_array( $theme ) || ! isset( $theme['kind'], $theme['colors'] ) || ! is_array( $theme['colors'] ) ||
			! in_array( $theme['kind'], array( 'core', 'builtin', 'custom' ), true ) ) {
			return $invalid;
		}
		if ( 'core' === $theme['kind'] ) {
			return array() === $theme['colors'] ? array( 'data' => '', 'errors' => array() ) : $invalid;
		}
		$preset = null;
		if ( 'builtin' === $theme['kind'] ) {
			$loaded = ( new Preset_Catalog() )->get( $theme['id'] ?? null, $theme['version'] ?? null );
			if ( $loaded['errors'] ) { return $invalid; }
			$preset = $loaded['data'];
			// Immutable built-ins must match every pinned value, including catalog-only data.
			if ( count( $theme['colors'] ) !== count( $preset['colors'] ) ) { return $invalid; }
			foreach ( $preset['colors'] as $property => $value ) {
				if ( ! array_key_exists( $property, $theme['colors'] ) || $theme['colors'][ $property ] !== $value ) { return $invalid; }
			}
		} else {
			if ( ! Theme_Validator::valid_id( $theme['id'] ?? null ) ) { return $invalid; }
			if ( isset( $theme['base_preset'] ) ) {
				$base = ( new Theme_Validator() )->base( $theme['base_preset'] );
				if ( $base['errors'] ) { return $invalid; }
				$preset = ( new Preset_Catalog() )->get( $base['data']['id'], $base['data']['version'] )['data'];
			}
		}
		$properties = Color_Properties::supported();
		foreach ( $theme['colors'] as $property => $value ) {
			$pinned = null !== $preset && array_key_exists( $property, $preset['colors'] ) && $value === $preset['colors'][ $property ];
			if ( ! in_array( $property, $properties, true ) ) {
				// Only exact catalog provenance permits omission; this is never emitted.
				if ( $pinned && in_array( $property, $preset['unsupported_properties'], true ) ) { continue; }
				return $invalid;
			}
			if ( ! Color_Properties::valid_hex( $value ) && ! $pinned ) { return $invalid; }
		}
		$declarations = '';
		foreach ( $properties as $property ) {
			if ( array_key_exists( $property, $theme['colors'] ) ) {
				$declarations .= $property . ':' . $theme['colors'][ $property ] . ';';
			}
		}
		return array( 'data' => $declarations, 'errors' => array() );
	}
}
