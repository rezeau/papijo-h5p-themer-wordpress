<?php
/** Pure creation/copy/edit preparation adapted from Backdrop v0.5.0 semantics. */
namespace Papijo\H5PThemerWordPress\Theme;

/** @license GPL-3.0-or-later */
final class Theme_Operations {
	/** Established builtin-copy metadata; never a playback Density preference. */
	const LEGACY_DENSITY_DEFAULT = 'large';
	public function duplicate( $source, $id, $label, $definitions = array() ) {
		$validator = new Theme_Validator();
		$resolved  = ( new Palette_Resolver() )->resolve( $source, $definitions );
		if ( $resolved['errors'] ) {
			return $resolved;
		}
		if ( ! is_array( $definitions ) || ! Theme_Validator::valid_id( $id ) || array_key_exists( $id, $definitions ) ) {
			return $this->error( 'destination_unavailable' );
		}
		$palette = $resolved['data'];
		if ( 'core' === $palette['kind'] ) {
			return $this->error( 'core_not_duplicable' );
		}
		if ( 'custom' === $palette['kind'] ) {
			// Copy stored sparse overrides and pin, not resolved expressions or a live parent.
			$candidate = $definitions[ $palette['id'] ];
		} else {
			$candidate = array( 'schema_version' => 2, 'density' => self::LEGACY_DENSITY_DEFAULT, 'colors' => array(),
				'base_preset' => array( 'id' => $palette['id'], 'version' => $palette['version'] ) );
		}
		$candidate['id']          = $id;
		$candidate['label']       = $label;
		$candidate['description'] = isset( $candidate['description'] ) ? $candidate['description'] : '';
		return $validator->definition( $id, $candidate );
	}

	/** Add starts from the global palette, falling back to pinned Daylight. */
	public function create_from_default( $global, $id, $label, $definitions = array() ) {
		$resolved = ( new Palette_Resolver() )->resolve( $global, $definitions );
		$fallback = $resolved['errors'] || 'core' === $resolved['data']['kind'];
		$source   = $fallback ? array( 'kind' => 'builtin', 'id' => 'daylight', 'version' => Preset_Catalog::VERSION ) : $global;
		$result   = $this->duplicate( $source, $id, $label, $definitions );
		if ( ! $result['errors'] ) { $result['data']['description'] = ''; }
		$result['fallback'] = (bool) $fallback;
		return $result;
	}

	/** Full editable override map: blank removes only a supported override. */
	public function with_overrides( $definition, $colors ) {
		if ( ! is_array( $definition ) || ! isset( $definition['id'] ) || ! is_array( $colors ) ) {
			return $this->error( 'invalid_override_input' );
		}
		$validator = new Theme_Validator();
		$original  = $validator->definition( $definition['id'], $definition );
		if ( $original['errors'] ) {
			return $original;
		}
		foreach ( $colors as $property => $value ) {
			if ( ! in_array( $property, Color_Properties::supported(), true ) ) {
				return $this->error( 'unsupported_color_property' );
			}
			if ( '' === $value ) {
				unset( $colors[ $property ] );
			}
		}
		$definition['colors'] = $colors;
		return $validator->definition( $definition['id'], $definition );
	}

	private function error( $code ) {
		return array( 'data' => null, 'errors' => array( $code ) );
	}
}
