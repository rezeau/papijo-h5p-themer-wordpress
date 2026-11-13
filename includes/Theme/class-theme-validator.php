<?php
/**
 * Validation adapted from Backdrop v0.5.0 configuration contracts.
 * Original grants: GPL-2.0-or-later. This adaptation: GPL-3.0-or-later.
 * @package Papijo\H5PThemerWordPress
 */
namespace Papijo\H5PThemerWordPress\Theme;

/** Does not normalize input, translate errors, read storage or apply a theme. */
final class Theme_Validator {
	public static function valid_id( $id ) {
		return is_string( $id ) && 1 === preg_match( '/\A[a-z][a-z0-9_]{0,63}\z/', $id );
	}

	public function definition( $id, $data ) {
		if ( ! is_array( $data ) ) {
			return $this->result( null, array( 'definition_not_object' ) );
		}
		$errors   = array();
		$required = array( 'schema_version', 'id', 'label', 'density', 'colors' );
		if ( isset( $data['schema_version'] ) && 2 === $data['schema_version'] ) {
			$required[] = 'base_preset';
		}
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				$errors[] = 'missing_' . $key;
			}
		}
		if ( array_diff( array_keys( $data ), array_merge( $required, array( 'description' ) ) ) ) {
			$errors[] = 'unknown_definition_field';
		}
		if ( ! isset( $data['schema_version'] ) || ! in_array( $data['schema_version'], array( 1, 2 ), true ) ) {
			$errors[] = 'invalid_definition_schema';
		}
		if ( ! self::valid_id( $id ) || ! isset( $data['id'] ) || $id !== $data['id'] ) {
			$errors[] = 'invalid_theme_id';
		}
		$errors = array_merge( $errors, $this->name_errors( $data ) );
		// Round-trip legacy metadata only. No resolver or preference reads this value.
		if ( ! isset( $data['density'] ) || ! in_array( $data['density'], array( 'large', 'medium', 'small' ), true ) ) {
			$errors[] = 'invalid_legacy_density';
		}
		$errors = array_merge( $errors, $this->color_errors( $data ) );
		if ( isset( $data['schema_version'] ) && 2 === $data['schema_version'] && array_key_exists( 'base_preset', $data ) ) {
			$errors = array_merge( $errors, $this->base( $data['base_preset'] )['errors'] );
		}
		return $this->result( $data, array_values( array_unique( $errors ) ) );
	}

	/** Colors-only transfer record; deliberately not a stored definition. */
	public function color_record( $data ) {
		if ( ! is_array( $data ) ) { return $this->result( null, array( 'invalid_color_record' ) ); }
		$required = array( 'id', 'label', 'base_preset', 'colors' );
		if ( array_diff( $required, array_keys( $data ) ) || array_diff( array_keys( $data ), array_merge( $required, array( 'description' ) ) ) ) {
			return $this->result( null, array( 'invalid_color_record_fields' ) );
		}
		$errors = self::valid_id( $data['id'] ) ? array() : array( 'invalid_theme_id' );
		$errors = array_merge( $errors, $this->name_errors( $data ), $this->color_errors( $data ) );
		if ( null !== $data['base_preset'] ) { $errors = array_merge( $errors, $this->base( $data['base_preset'] )['errors'] ); }
		return $this->result( $data, array_values( array_unique( $errors ) ) );
	}

	private function name_errors( array $data ) {
		$errors = array();
		if ( ! isset( $data['label'] ) || ! is_string( $data['label'] ) || '' === trim( $data['label'] ) ||
			strlen( $data['label'] ) > 255 || 1 !== preg_match( '//u', $data['label'] ) || preg_match( '/[\x00-\x1F\x7F]/', $data['label'] ) ) {
			$errors[] = 'invalid_label';
		}
		if ( array_key_exists( 'description', $data ) && ( ! is_string( $data['description'] ) || strlen( $data['description'] ) > 4096 ||
			1 !== preg_match( '//u', $data['description'] ) || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $data['description'] ) ) ) {
			$errors[] = 'invalid_description';
		}
		return $errors;
	}

	private function color_errors( array $data ) {
		$errors = array();
		if ( ! isset( $data['colors'] ) || ! is_array( $data['colors'] ) ) {
			$errors[] = 'invalid_colors';
		} else {
			foreach ( $data['colors'] as $property => $value ) {
				if ( ! in_array( $property, Color_Properties::supported(), true ) ) {
					$errors[] = 'unsupported_color_property';
				}
				if ( ! Color_Properties::valid_hex( $value ) ) {
					$errors[] = 'invalid_hex_color';
				}
			}
		}
		return $errors;
	}

	public function base( $reference ) {
		if ( ! is_array( $reference ) || 2 !== count( $reference ) || ! array_key_exists( 'id', $reference ) || ! array_key_exists( 'version', $reference ) ) {
			return $this->result( null, array( 'invalid_preset_reference' ) );
		}
		$preset = ( new Preset_Catalog() )->get( $reference['id'], $reference['version'] );
		return $this->result( $reference, $preset['errors'] );
	}

	/** Schema 1 strings mean custom IDs. Site default is never a stored global. */
	public function reference( $selection, $schema = 2 ) {
		$error = $this->result( null, array( 'invalid_theme_reference' ) );
		if ( ! in_array( $schema, array( 1, 2 ), true ) ) {
			return $error;
		}
		if ( null === $selection ) {
			return $this->result( array( 'kind' => 'core' ), array() );
		}
		if ( 1 === $schema ) {
			return self::valid_id( $selection ) ? $this->result( array( 'kind' => 'custom', 'id' => $selection ), array() ) : $error;
		}
		if ( ! is_array( $selection ) || ! isset( $selection['kind'], $selection['id'] ) ) {
			return $error;
		}
		if ( 'custom' === $selection['kind'] && 2 === count( $selection ) && self::valid_id( $selection['id'] ) ) {
			return $this->result( $selection, array() );
		}
		if ( 'builtin' === $selection['kind'] && 3 === count( $selection ) && array_key_exists( 'version', $selection ) ) {
			$preset = ( new Preset_Catalog() )->get( $selection['id'], $selection['version'] );
			return $this->result( $selection, $preset['errors'] );
		}
		return $error;
	}

	private function result( $data, array $errors ) {
		return array( 'data' => $errors ? null : $data, 'errors' => $errors );
	}
}
